<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\AttendancesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/attendances', [AttendancesController::class, 'worker'])->name('attendances.worker');
Route::post('/attendances', [AttendancesController::class, 'workerStore'])->middleware('throttle:5,1')->name('attendances.worker.store');
Route::post('/attendances/history', [AttendancesController::class, 'workerHistory'])->middleware('throttle:10,1')->name('attendances.worker.history');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
