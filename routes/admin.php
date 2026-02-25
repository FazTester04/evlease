<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\Admin\CarsController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\DriverController;

Route::middleware(['admin.auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        // Redirect '/admin' to '/admin/cars'
        Route::redirect('/', '/admin/cars')->name('home');

        // Cars – Fleet management
        Route::get('cars', [FleetController::class, 'index'])->name('cars.index');
        Route::put('cars/{car}', [CarsController::class, 'update'])->name('cars.update');
        Route::delete('cars/{car}', [CarsController::class, 'destroy'])->name('cars.destroy');
        Route::post('cars', [CarsController::class, 'store'])->name('cars.store');
        // Leases
        Route::put('leases/{lease}', [LeaseController::class, 'update'])->name('leases.update');
        Route::delete('leases/{lease}', [LeaseController::class, 'destroy'])->name('leases.destroy');
        Route::post('leases', [LeaseController::class, 'store'])->name('leases.store');
        // Documents
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
        // Document upload
         Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');
// Record payment
        Route::post('payments/record', [PaymentsController::class, 'record'])->name('payments.record');
        // ... other admin routes (reservations, clients, payments, reports, support) ...
        Route::delete('payments/{payment}', [PaymentsController::class, 'destroy'])->name('payments.destroy');
        Route::get('vehicles', [FleetController::class, 'vehicles'])->name('vehicles.index');
Route::get('leases', [LeaseController::class, 'index'])->name('leases.index');
Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
Route::get('payments', [PaymentsController::class, 'index'])->name('payments.index');
Route::get('drivers', [FleetController::class, 'drivers'])->name('drivers.index');
Route::get('maintenance', [FleetController::class, 'maintenance'])->name('maintenance.index'); 

Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
    });