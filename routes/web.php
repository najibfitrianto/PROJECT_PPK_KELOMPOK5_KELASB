<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Public routes (No login required)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/facilities', [HomeController::class, 'facilities'])->name('facilities');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reservations
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    // Reports
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

    // Petugas & Admin actions
    Route::post('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject', [ReservationController::class, 'reject'])->name('reservations.reject');
    Route::post('/reports/{report}/process', [ReportController::class, 'process'])->name('reports.process');
    Route::post('/reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');

    // Admin only actions
    Route::post('/admin/users/{user}/verify', [AdminController::class, 'verifyUser'])->name('admin.users.verify');
    Route::post('/admin/users/{user}/reject', [AdminController::class, 'rejectUser'])->name('admin.users.reject');
    Route::post('/admin/users/{user}/toggle-block', [AdminController::class, 'toggleBlockUser'])->name('admin.users.toggle-block');
    Route::post('/admin/petugas', [AdminController::class, 'createPetugas'])->name('admin.petugas.store');

    Route::post('/admin/facilities', [AdminController::class, 'storeFacility'])->name('admin.facilities.store');
    Route::post('/admin/facilities/{facility}', [AdminController::class, 'updateFacility'])->name('admin.facilities.update');
    Route::delete('/admin/facilities/{facility}', [AdminController::class, 'destroyFacility'])->name('admin.facilities.destroy');
});
