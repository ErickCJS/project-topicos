<?php

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\CambioController;
use App\Http\Controllers\admin\BulkChangeController;
use App\Http\Controllers\admin\MotivoController;
use App\Http\Controllers\admin\ProgrammingController;
use App\Http\Controllers\admin\AttendancesController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\BrandModelController;
use App\Http\Controllers\admin\ColorController;
use App\Http\Controllers\admin\HolidaysController;
use App\Http\Controllers\admin\ContractController;
use App\Http\Controllers\admin\EmployeeController;
use App\Http\Controllers\admin\EmployeeTypeController;
use App\Http\Controllers\admin\ReasonController;
use App\Http\Controllers\admin\ShiftController;
use App\Http\Controllers\admin\StaffGroupsController;
use App\Http\Controllers\admin\UserController;
use App\Http\Controllers\admin\VacationController;
use App\Http\Controllers\admin\ZoneController;
use App\Http\Controllers\admin\VehiclesController;
use App\Http\Controllers\admin\VehicletypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminController::class, 'index'])->name('admin.home');
Route::get('programming', [ProgrammingController::class, 'index'])->name('programming.index');
Route::get('programming/holidays', [ProgrammingController::class, 'holidays'])->name('programming.holidays');
Route::get('programming/people', [ProgrammingController::class, 'people'])->name('programming.people');
Route::get('programming/groups/{staffGroup}', [ProgrammingController::class, 'group'])->name('programming.group');
Route::post('programming/bulk/context', [ProgrammingController::class, 'bulkContext'])->name('programming.bulk.context');
Route::post('programming/bulk/check', [ProgrammingController::class, 'bulkCheck'])->name('programming.bulk.check');
Route::post('programming/bulk/generate', [ProgrammingController::class, 'bulkGenerate'])->name('programming.bulk.generate');
Route::post('programming/bulk/undo', [ProgrammingController::class, 'bulkUndo'])->name('programming.bulk.undo');
Route::post('programming/bulk-modifications/check', [ProgrammingController::class, 'bulkModificationCheck'])->name('programming.bulk-modifications.check');
Route::post('programming/bulk-modifications/options', [ProgrammingController::class, 'bulkModificationOptions'])->name('programming.bulk-modifications.options');
Route::post('programming/bulk-modifications/apply', [ProgrammingController::class, 'bulkModificationApply'])->name('programming.bulk-modifications.apply');
Route::get('cambios/masivos', [BulkChangeController::class, 'index'])->name('cambios.bulk.index');
Route::post('cambios/masivos/options', [BulkChangeController::class, 'options'])->name('cambios.bulk.options');
Route::post('cambios/masivos/check', [BulkChangeController::class, 'check'])->name('cambios.bulk.check');
Route::post('cambios/masivos/apply', [BulkChangeController::class, 'apply'])->name('cambios.bulk.apply');
Route::post('programming/check', [ProgrammingController::class, 'check'])->name('programming.check');
Route::post('programming', [ProgrammingController::class, 'store'])->name('programming.store');
Route::get('programming/{id}/history', [ProgrammingController::class, 'history'])->name('programming.history');
Route::get('programming/{programming}/modifications', [ProgrammingController::class, 'modifications'])->name('programming.modifications');
Route::get('programming/{programming}/modification-options', [ProgrammingController::class, 'modificationOptions'])->name('programming.modification-options');
Route::patch('programming/{programming}/modifications', [ProgrammingController::class, 'updateModifications'])->name('programming.update-modifications');
Route::post('programming/{programming}/finish', [ProgrammingController::class, 'finish'])->name('programming.finish');
Route::patch('programming/{programming}/annul', [ProgrammingController::class, 'annul'])->name('programming.annul');
Route::get('programming/{programming}', [ProgrammingController::class, 'show'])->name('programming.show');
Route::put('programming/{programming}', [ProgrammingController::class, 'update'])->name('programming.update');
Route::get('programming/{programming}/rescheduling/options', [ProgrammingController::class, 'reschedulingOptions'])->name('programming.rescheduling.options');
Route::post('programming/{programming}/rescheduling/check', [ProgrammingController::class, 'reschedulingCheck'])->name('programming.rescheduling.check');
Route::post('programming/{programming}/rescheduling', [ProgrammingController::class, 'reschedule'])->name('programming.rescheduling.store');
Route::get('programming/{programming}/related', [ProgrammingController::class, 'related'])->name('programming.related');
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

Route::get('attendances/history', [AttendancesController::class, 'history'])->name('attendances.history');
Route::get('attendances/suggestion', [AttendancesController::class, 'suggest'])->name('attendances.suggest');
Route::resource('attendances', AttendancesController::class)->names('attendances');
Route::get('holidays/resumen', [HolidaysController::class, 'resumenFeriados'])
    ->name('holidays.resumen');

Route::post('holidays/cargaferiado', [HolidaysController::class, 'cargaFeriado'])
    ->name('holidays.cargaFeriado');

Route::resource('holidays', HolidaysController::class)
    ->names('holidays');
Route::resource('shifts', ShiftController::class)->names('shifts');
Route::resource('reason', ReasonController::class)->names('reason');
Route::resource('cambios', CambioController::class)->names('cambios');

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


Route::resource('users', UserController::class)->names('users');
