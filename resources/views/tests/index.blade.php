<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Latest compiled and minified CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <title>Test</title>
</head>
<body>
    <div class="container mt-2">
        <div class="row">
            <!-- Right-aligned Export CSV button -->
            <div class="col-12 text-end">
                @if ($data > 0)
                    <a href="{{ route('tests.export') }}" class="btn btn-primary">Export CSV</a>
                @else
                    <button class="btn btn-primary" disabled>Export CSV</button>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-2">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mt-2">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tests.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="upload-type">Select Upload Type:</label>
                <select name="upload_type" id="upload-type" class="form-control">
                    <option value="" disabled selected>Select Type</option>
                    <option value="users">Users</option>
                    <option value="service_providers">Service Providers</option>
                </select>
            </div>
            <div class="form-group mb-2">
                <label for="file">Choose File:</label>
                <input type="file" name="file" id="file" class="form-control" accept=".csv">
            </div>
            <button type="submit" id="upload-button" class="btn btn-primary form-control" disabled>Upload</button>
        </form>


        <div class="row mt-2">
            <!-- Right-aligned Export CSV button -->
            <div class="col-12 text-end">
                <p class="fw-bold">Inactive Users : <span class="text-danger"> {{ $inactiveUsers }}</span></p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th >Service Provider</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($technicians as $technician)
                    <tr>
                        <td>{{ $technician->service_provider }}</td>
                        <td>{{ $technician->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No technicians found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <script src="{{ asset('js/index.js')}}"></script>
</body>
</html>
