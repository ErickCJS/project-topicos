<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\BrandModelController;
use App\Http\Controllers\admin\VehicleColorController;
use App\Http\Controllers\admin\VehiclesController;
use App\Http\Controllers\admin\VehicletypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index']);
Route::resource('brands', BrandController::class)->names('brands');
Route::get('vehicles', [VehiclesController::class, 'index'])->name('vehicles.index');
Route::resource('brandsmodel', BrandModelController::class)->names('brandsmodel');
Route::resource('brandstype', VehicletypeController::class)->names('vehiclestype');
Route::resource('vehiclescolor', VehicleColorController::class)->names('vehiclescolor');
