<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\Auth\AdminAuthController;

// Redirect root to dashboard (will be protected)
Route::redirect('/', '/admin/login');

// Public admin login routes – accessible without authentication
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('login.store');
});

// Protected dashboard routes – only authenticated admins can access
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/ev-dashboard', [DashboardController::class, 'index'])->name('ev.dashboard');
});

// Include authentication routes (login, register, etc.)
require __DIR__.'/auth.php';

// Include admin routes – they are already protected inside their own file
require __DIR__.'/admin.php';