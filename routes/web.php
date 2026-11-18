<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\System\DivisionController;
use App\Http\Controllers\System\OverrideController;
use App\Http\Controllers\System\RealtimeTestController;
use App\Http\Controllers\System\UserController;
use App\Http\Controllers\System\WorkCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    Route::get('/password/reset', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::prefix('system')->name('system.')->group(function () {
        Route::resource('divisions', DivisionController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('users/{user}/reset-password', [UserController::class, 'editPassword'])->name('users.edit-password');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('work-calendars', WorkCalendarController::class)->except('show');
        Route::get('overrides', [OverrideController::class, 'index'])->name('overrides.index');
        Route::get('overrides/create', [OverrideController::class, 'create'])->name('overrides.create');
        Route::post('overrides', [OverrideController::class, 'store'])->name('overrides.store');
    });
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');
});

Route::get('/broadcast-test', RealtimeTestController::class)
    ->middleware('auth')
    ->name('broadcast-test');
