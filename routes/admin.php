<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\AttendancesController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\BrandModelController;
use App\Http\Controllers\admin\ColorController;
use App\Http\Controllers\admin\HolidaysController;
use App\Http\Controllers\admin\ContractController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\EmployeeTypeController;
use App\Http\Controllers\admin\ShiftController;
use App\Http\Controllers\admin\VacationController;
use App\Http\Controllers\admin\VehiclesController;
use App\Http\Controllers\admin\VehicletypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.home');
Route::resource('contracts', ContractController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])->names('contracts');
Route::patch('contracts/{id}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
Route::get('vacations/check', [VacationController::class, 'check'])->name('vacations.check');
Route::get('vacations/balance', [VacationController::class, 'balance'])->name('vacations.balance');
Route::resource('vacations', VacationController::class)->names('vacations');
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

Route::resource('attendances', AttendancesController::class)->names('attendances');
Route::get('holidays/resumen', [HolidaysController::class, 'resumenFeriados'])
    ->name('holidays.resumen');

Route::post('holidays/cargaferiado', [HolidaysController::class, 'cargaFeriado'])
    ->name('holidays.cargaFeriado');

Route::resource('holidays', HolidaysController::class)
    ->names('holidays');
Route::resource('shifts', ShiftController::class)->names('shifts');
    
Route::get('retornar_vista_imagenes/{id_product}', [VehiclesController::class, 'retornar_vista_imagenes'])->name('vehicles.photo.retornar_vista_imagenes');
Route::post('subirimagenes/{id_products}', [VehiclesController::class, 'subirimagenes'])->name('vehicles.photo.subirimagenes');
Route::get('retonar_rutas_fotos/{id_product}', [VehiclesController::class, 'retonar_rutas_fotos'])->name('vehicles.photo.retonar_rutas_fotos');
Route::delete('eliminar_imagen/{id_product}', [VehiclesController::class, 'eliminar_imagen'])->name('vehicles.photo.eliminar_imagen');
Route::patch('photos/{id_foto}/seleccionar', [VehiclesController::class, 'seleccionar_foto_principal'])->name('vehicles.photo.seleccionar');
