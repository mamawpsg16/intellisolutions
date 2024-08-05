<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Post;
use App\Models\Role;
use App\Models\TestUser;
use Illuminate\Http\Request;
use App\Models\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\UploadRequest;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $inactiveUsers  = TestUser::whereNull('last_login_time')
            ->orWhere('last_login_time', '<', now()->subDays(30))
            ->count();

            $data = $this->getDataToText()->count();

            $technicians = $this->getTechniciansToText();
            
            return view('tests.index',['inactiveUsers' => $inactiveUsers , 'data' => $data, 'technicians' => $technicians]);

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
        
    }

    public function upload(UploadRequest $request)
    {
        $request->validated();

        $uploadType = $request->input('upload_type');
        
        $file = $request->file('file');

        $path = $file->storeAs('uploads', $uploadType, 'public');
        
        $fullPath = public_path('storage/' . $path);

        try {

            DB::transaction(function () use ($fullPath, $uploadType) {
                $this->importCsv($fullPath, $uploadType);
            });

            return back()->with('success', 'File uploaded and data imported successfully.');
        } catch (Exception $e) {

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    
    private function importCsv($filePath, $uploadType)
    {
        if (($handle = fopen($filePath, 'r')) !== false) {

            $header = fgetcsv($handle);

            if (!$this->validateCsv($header, $uploadType)) {
                fclose($handle);
                throw new Exception('Invalid Data in CSV file for upload type: ' . $uploadType);
            }

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
    
               $uploadType == 'users' ? $this->insertUsers($data) :  $this->insertServiceProviders($data);
              
            }
    
            fclose($handle);
        } else {
            throw new Exception('Unable to open the CSV file.');
        }
    }
    
    private function validateCsv($csvFields, $uploadType)
    {
        $requiredFields = $this->getUploaderFields($uploadType);

        foreach ($requiredFields as $field) {
            if (!in_array($field, $csvFields) || count($csvFields) !=  count($requiredFields) ) {
                return false;
            }
        }

        return true;
    }

    private function insertUsers($data){


        $id = $data['id'];
        $serviceProviderId = $data['service_provider_id'];
        $lastLoginTime = $data['last_login_time'] ? Carbon::createFromFormat('d/m/y H:i', $data['last_login_time'])->toDateTimeString() : null;
        $name = $data['name'];
        $roles = $data['roles'];
        
        $sql = "
            INSERT INTO test_users (id, service_provider_id, last_login_time, name, roles)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT (id)
            DO UPDATE SET
                service_provider_id = EXCLUDED.service_provider_id,
                last_login_time = EXCLUDED.last_login_time,
                name = EXCLUDED.name,
                roles = EXCLUDED.roles
        ";
        
        DB::statement($sql, [$id, $serviceProviderId, $lastLoginTime, $name, $roles]);
    }

    private function insertServiceProviders($data){
        $id = $data['id'];
        $name = $data['name'];
        
        $sql = "
            INSERT INTO service_providers (id, name)
            VALUES (?, ?)
            ON CONFLICT (id)
            DO UPDATE SET
                name = EXCLUDED.name
        ";
        
        DB::statement($sql, [$id, $name]);
    }

    private function getUploaderFields($uploadType){
        $users  = ['id', 'service_provider_id', 'roles', 'last_login_time', 'name'];
        $serviceProviders = ['id', 'name'];

        return $uploadType === 'users' ? $users : $serviceProviders;
    }

    public function export()
    {
        try {
            // Fetch data for export
            $users = $this->getDataToText();

            // Check if data exists
            if (empty($users)) {
                throw new Exception('No Data Exists');
            }

            // Log data for debugging (replace dd)
            Log::info('Export Data:', [$users]);

            $csvFileName = 'data_export.csv';

            // Create and return StreamedResponse
            $response = new StreamedResponse(function () use ($users) {
                $handle = fopen('php://output', 'w');

                // Add CSV headers
                fputcsv($handle, [
                    'Service provider name',
                    'User name',
                    'Last login',
                    'Days since the last login',
                    'Is Technician',
                    'Is SuperDispatcher',
                    'Is Dispatcher',
                    'Is SystemManager',
                    'Is NEATechnician',
                    'Is DutyManager',
                    'Is TechnicianTeam'
                ]);

                // Process each user for CSV output
                foreach ($users as $item) {
                    // Log current item for debugging
                    Log::info('Processing Item:', (array) $item);

                    // Process roles
                    $roles = !empty($item->role) ? explode(',', $item->role) : [];
                    $flags = $this->mapRolesToFlags($roles);

                    // Extract role flags
                    $isTechnician = $flags['Technician'] ?? 0;
                    $isSuperDispatcher = $flags['SuperDispatcher'] ?? 0;
                    $isDispatcher = $flags['Dispatcher'] ?? 0;
                    $isSystemManager = $flags['SystemManager'] ?? 0;
                    $isNEATechnician = $flags['NEATechnician'] ?? 0;
                    $isDutyManager = $flags['DutyManager'] ?? 0;
                    $isTechnicianTeam = $flags['TechnicianTeam'] ?? 0;

                    // Calculate days since last login
                    if (empty($item->last_login_time)) {
                        $lastLogin = 'Never login';
                        $daysSinceLastLogin = '';
                    } else {
                        $lastLogin = $item->last_login_time;
                        $lastLoginDate = new Carbon($item->last_login_time);
                        $now = Carbon::now();
                        $daysSinceLastLogin = $now->diffInDays($lastLoginDate);
                    }

                    // Write to CSV
                    fputcsv($handle, [
                        $item->provider,
                        $item->user_name,
                        $lastLogin,
                        $daysSinceLastLogin,
                        $isTechnician,
                        $isSuperDispatcher,
                        $isDispatcher,
                        $isSystemManager,
                        $isNEATechnician,
                        $isDutyManager,
                        $isTechnicianTeam
                    ]);
                }

                fclose($handle);
            });

            // Set response headers
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $csvFileName . '"');

            return $response;

        } catch (Exception $e) {
            Log::error('Error exporting data: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    

    private function getDataToText() {
        return DB::table('test_users')
            ->join('service_providers', 'test_users.service_provider_id', '=', 'service_providers.id')
            ->select(
                'service_providers.name as provider',
                'test_users.name as user_name',
                'test_users.last_login_time',
                'test_users.roles'
                // DB::raw('unnest(string_to_array(test_users.roles, \',\')) AS role')
            )
            ->orderBy('service_providers.name')
            ->get();
    }
    
    
    private function getDataToJson() {
        return DB::table('test_users')
            ->join('service_providers', 'test_users.service_provider_id', '=', 'service_providers.id')
            ->select(
                'service_providers.name as provider',
                'test_users.name as user_name',
                'test_users.last_login_time',
                'test_users.roles'
            )
            ->orderBy('service_providers.name')
            ->get();
    }
    

    private function getTechniciansToJson() {
        $query = '
            WITH user_roles AS (
                SELECT
                    tu.service_provider_id,
                    jsonb_array_elements_text(tu.roles) AS role
                FROM test_users tu
            )
            SELECT
                sp.name AS "service_provider",
                COUNT(ur.service_provider_id) AS "total"
            FROM
                service_providers sp
            LEFT JOIN user_roles ur ON sp.id = ur.service_provider_id
            WHERE
                ur.role = :role
            GROUP BY sp.name, ur.role
        ';
         $results = DB::select($query, ['role' => 'Technician']);

         return json_decode(json_encode($results), true);
    }

    private function getTechniciansToText()
    {
        try {
            $query = "
                WITH user_roles AS (
                    SELECT
                        tu.service_provider_id,
                        unnest(string_to_array(tu.roles, ',')) AS role
                    FROM test_users tu
                )
                SELECT
                    sp.name AS service_provider,
                    COUNT(ur.service_provider_id) AS total
                FROM
                    service_providers sp
                LEFT JOIN user_roles ur ON sp.id = ur.service_provider_id
                WHERE ur.role = :role
                   
                GROUP BY sp.name
                ORDER BY sp.name
            ";

            return DB::select($query, ['role' => 'Technician']);
        } catch (Exception $e) {
            Log::error('Error fetching technicians: ' . $e->getMessage());
            throw $e;
        }
    }

    private function mapRolesToFlags(array $roles): array
    {
        $allRoles = [
            'Technician',
            'SuperDispatcher',
            'Dispatcher',
            'SystemManager',
            'NEATechnician',
            'DutyManager',
            'TechnicianTeam'
        ];

        $flags = array_fill_keys($allRoles, 0);

        // Set flags to 1 for roles that are present in the input array
        foreach ($roles as $role) {
            if (array_key_exists($role, $flags)) {
                $flags[$role] = 1;
            }
        }

        return $flags;
    }
    

}
