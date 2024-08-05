<?php

use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [TestController::class, 'index'])->name('tests.index');
Route::post('/upload', [TestController::class, 'upload'])->name('tests.upload');
Route::get('/export', [TestController::class, 'export'])->name('tests.export');
