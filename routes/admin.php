<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\AttendancesController;
use App\Http\Controllers\admin\ContractController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\BrandModelController;
use App\Http\Controllers\admin\EmployeeTypeController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\ColorController;
use App\Http\Controllers\admin\VehiclesController;
use App\Http\Controllers\admin\VehicletypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.home');
Route::resource('contracts', ContractController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])->names('contracts');
Route::patch('contracts/{id}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
Route::resource('brands', BrandController::class)->names('brands');
Route::resource('vehicles', VehiclesController::class)->names('vehicles');
Route::get('vehicles/retonar_models/{id_brand}', [VehiclesController::class, 'retonar_models'])->name('vehicles.retonar_models');

Route::resource('brandsmodel', BrandModelController::class)->names('brandsmodel');
Route::resource('employee-types', EmployeeTypeController::class)->names('employee-types');
Route::resource('personal/employees', EmployeeController::class)->except(['create'])->names('personal.employees');
Route::resource('vehiclestype', VehicletypeController::class)->names('vehiclestype');
Route::resource('vehiclescolor', ColorController::class)
    ->only(['index', 'store', 'show', 'edit', 'update', 'destroy'])
    ->names('vehiclescolor');

Route::get('attendances/history', [AttendancesController::class, 'history'])->name('attendances.history');
Route::resource('attendances', AttendancesController::class)->names('attendances');


Route::get('retornar_vista_imagenes/{id_product}', [VehiclesController::class, 'retornar_vista_imagenes'])->name('vehicles.photo.retornar_vista_imagenes');
Route::post('subirimagenes/{id_products}', [VehiclesController::class, 'subirimagenes'])->name('vehicles.photo.subirimagenes');
Route::get('retonar_rutas_fotos/{id_product}', [VehiclesController::class, 'retonar_rutas_fotos'])->name('vehicles.photo.retonar_rutas_fotos');
Route::delete('eliminar_imagen/{id_product}', [VehiclesController::class, 'eliminar_imagen'])->name('vehicles.photo.eliminar_imagen');
Route::patch('photos/{id_foto}/seleccionar', [VehiclesController::class, 'seleccionar_foto_principal'])->name('vehicles.photo.seleccionar');
