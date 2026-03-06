<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\Admin\CarsController;
use App\Http\Controllers\LeaseController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\VehicleController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\OverduePaymentsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserSettingsController; // <-- added

Route::middleware(['admin.auth', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        // Redirect
        Route::redirect('/', '/admin/cars')->name('home');

        // Cars
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
        Route::post('documents/upload', [DocumentController::class, 'upload'])->name('documents.upload');

        // Payments
        Route::post('payments/record', [PaymentsController::class, 'record'])->name('payments.record');
        Route::post('/payments/{payment}/approve', [PaymentsController::class, 'approve'])->name('admin.payments.approve');
        Route::delete('payments/{payment}', [PaymentsController::class, 'destroy'])->name('payments.destroy');

        // Listings
        Route::get('vehicles', [FleetController::class, 'vehicles'])->name('vehicles.index');
        Route::get('leases', [LeaseController::class, 'index'])->name('leases.index');
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('payments', [PaymentsController::class, 'index'])->name('payments.index');

        // Drivers
        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        // Dashboard
        Route::get('/ev-dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Maintenance management
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::patch('maintenance/{maintenance}/complete', [MaintenanceController::class, 'complete'])
            ->name('maintenance.complete');

        // Set maintenance from vehicle page
        Route::patch('/vehicles/{vehicle}/maintenance', [VehicleController::class, 'setMaintenance'])
            ->name('admin.vehicles.maintenance');

        // Overdue payments
        Route::get('overdue-payments', [OverduePaymentsController::class, 'index'])
            ->name('admin.overdue');

        // Settings group – separate pages
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('late-fee', [SettingsController::class, 'lateFee'])->name('late-fee');
            Route::post('late-fee', [SettingsController::class, 'updateLateFee'])->name('late-fee.update');

            Route::get('users', [UserSettingsController::class, 'index'])->name('users');
            Route::patch('users/{user}/role', [UserSettingsController::class, 'updateRole'])->name('users.role');
            Route::delete('users/{user}', [UserSettingsController::class, 'destroy'])->name('users.destroy');
        });
    });
