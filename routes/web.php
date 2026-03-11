<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Driver\DashboardController as DriverDashboardController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\Driver\PaymentController;
use App\Http\Controllers\Driver\DocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Driver\VehicleController;
use App\Http\Controllers\ChatController;

// Redirect root to the unified login page
Route::redirect('/', '/login');

// Serve public storage files (works without storage:link; avoids 403 on /storage)
Route::get('/files/{path}', function (string $path): BinaryFileResponse {
    $path = str_replace(['..', '\\'], ['', '/'], $path);
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return response()->file(Storage::disk('public')->path($path), [
        'Content-Type' => Storage::disk('public')->mimeType($path),
    ]);
})->where('path', '.*')->name('storage.serve');

// Protected routes for authenticated users
Route::middleware('auth')->group(function () {
    // Driver dashboard
    Route::get('/dashboard', [DriverDashboardController::class, 'index'])->name('dashboard');

    // Admin-only routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin/reports/export', [ReportController::class, 'export'])->name('admin.reports.export');
    });

    // API route for lease statement
    Route::get('/api/lease/{lease}/statement', [PaymentsController::class, 'statement'])
        ->name('api.lease.statement');

    // All driver routes under one prefix group
    Route::prefix('driver')->name('driver.')->group(function () {
        Route::resource('leases', App\Http\Controllers\Driver\LeaseController::class)
            ->only(['index', 'show']);
        Route::resource('documents', DocumentController::class)
            ->only(['index', 'create', 'store', 'destroy']);
        Route::resource('payments', PaymentController::class)
            ->only(['index', 'create', 'store']);
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    });

    // Chat routes (accessible to both admin and drivers)
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/{user}', [ChatController::class, 'show'])->name('show');
        Route::post('/message', [ChatController::class, 'store'])->name('store');
    });
});

// Include authentication routes (login, logout, etc.)
require __DIR__ . '/auth.php';

// Include admin routes – these are already protected inside admin.php
require __DIR__ . '/admin.php';
