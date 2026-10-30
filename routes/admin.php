<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\ProgrammingController;
use App\Http\Controllers\admin\AttendancesController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\BrandModelController;
use App\Http\Controllers\admin\ColorController;
use App\Http\Controllers\admin\HolidaysController;
use App\Http\Controllers\admin\ContractController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\EmployeeTypeController;
use App\Http\Controllers\admin\ShiftController;
use App\Http\Controllers\admin\StaffGroupsController;
use App\Http\Controllers\admin\VacationController;
use App\Http\Controllers\admin\ZoneController;
use App\Http\Controllers\admin\VehiclesController;
use App\Http\Controllers\admin\VehicletypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.home');
Route::get('programming', [ProgrammingController::class, 'index'])->name('programming.index');
Route::get('programming/holidays', [ProgrammingController::class, 'holidays'])->name('programming.holidays');
Route::get('programming/groups/{staffGroup}', [ProgrammingController::class, 'group'])->name('programming.group');
Route::post('programming/check', [ProgrammingController::class, 'check'])->name('programming.check');
Route::post('programming', [ProgrammingController::class, 'store'])->name('programming.store');
Route::get('programming/{id}/history', [ProgrammingController::class, 'history'])->name('programming.history');
Route::post('programming/{programming}/finish', [ProgrammingController::class, 'finish'])->name('programming.finish');
Route::get('programming/{programming}', [ProgrammingController::class, 'show'])->name('programming.show');
Route::put('programming/{programming}', [ProgrammingController::class, 'update'])->name('programming.update');
Route::delete('programming/{programming}', [ProgrammingController::class, 'destroy'])->name('programming.destroy');
Route::resource('contracts', ContractController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update'])->names('contracts');
Route::patch('contracts/{id}/cancel', [ContractController::class, 'cancel'])->name('contracts.cancel');
Route::get('vacations/check', [VacationController::class, 'check'])->name('vacations.check');
Route::get('vacations/balance', [VacationController::class, 'balance'])->name('vacations.balance');
Route::post('vacations/{vacation}/approve', [VacationController::class, 'approve'])->name('vacations.approve');
Route::post('vacations/{vacation}/reject', [VacationController::class, 'reject'])->name('vacations.reject');
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

Route::resource('staffgroups', StaffGroupsController::class)->names('staffgroups');

Route::get('zones/departments/{department}/provinces', [ZoneController::class, 'provinces'])->name('zones.provinces');
Route::get('zones/provinces/{province}/districts', [ZoneController::class, 'districts'])->name('zones.districts');
Route::get('zones/perimeters', [ZoneController::class, 'perimeters'])->name('zones.perimeters');
Route::resource('zones', ZoneController::class)->names('zones');
Route::get('/helpers', [StaffGroupsController::class ,'helpers'])->name('staffgroups.helpers');

Route::get('retornar_vista_imagenes/{id_product}', [VehiclesController::class, 'retornar_vista_imagenes'])->name('vehicles.photo.retornar_vista_imagenes');
Route::post('subirimagenes/{id_products}', [VehiclesController::class, 'subirimagenes'])->name('vehicles.photo.subirimagenes');
Route::get('retonar_rutas_fotos/{id_product}', [VehiclesController::class, 'retonar_rutas_fotos'])->name('vehicles.photo.retonar_rutas_fotos');
Route::delete('eliminar_imagen/{id_product}', [VehiclesController::class, 'eliminar_imagen'])->name('vehicles.photo.eliminar_imagen');
Route::patch('photos/{id_foto}/seleccionar', [VehiclesController::class, 'seleccionar_foto_principal'])->name('vehicles.photo.seleccionar');
