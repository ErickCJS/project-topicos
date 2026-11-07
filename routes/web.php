<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\BrandController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeAttendanceController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('employee')->name('employee.')->group(function () {
    Route::get('/login', [EmployeeAttendanceController::class, 'login'])->name('login');
    Route::post('/login', [EmployeeAttendanceController::class, 'authenticate'])->middleware('throttle:5,1')->name('authenticate');
    Route::get('/', [EmployeeAttendanceController::class, 'login'])->name('home');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.home');
    })->name('dashboard');
});
