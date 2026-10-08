<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\VehiclesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index']);
Route::resource('brands', BrandController::class)->names('brands');
Route::get('vehicles', [VehiclesController::class, 'index'])->name('vehicles.index');

