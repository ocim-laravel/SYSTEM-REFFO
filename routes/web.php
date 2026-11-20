<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Delivery\DeliveryController;
use App\Http\Controllers\Design\DesignController;
use App\Http\Controllers\Finishing\FinishingJobController;
use App\Http\Controllers\Finishing\FinishingQueueController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Production\ProductionJobController;
use App\Http\Controllers\Production\ProductionQueueController;
use App\Http\Controllers\Production\ProductionReprintController;
use App\Http\Controllers\System\DivisionController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\System\OverrideController;
use App\Http\Controllers\System\RealtimeTestController;
use App\Http\Controllers\System\SettingController;
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

    Route::resource('customers', CustomerController::class);

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/orders/{order}/validate', [OrderController::class, 'validate'])->name('orders.validate');
    Route::get('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancelStore'])->name('orders.cancel.store');
    Route::get('/orders/{order}/pdf', [OrderController::class, 'exportPdf'])->name('orders.pdf');

    Route::get('/design/queue', [DesignController::class, 'index'])->name('design.queue');
    Route::post('/design/{item}/claim', [DesignController::class, 'claim'])->name('design.claim');

    Route::get('/design/{item}/upload', [DesignController::class, 'uploadForm'])->name('design.upload');
    Route::post('/design/{item}/upload', [DesignController::class, 'upload'])->name('design.upload');
    Route::post('/design/{item}/revise', [DesignController::class, 'revise'])->name('design.revise');
    Route::post('/design/{item}/submit', [DesignController::class, 'submit'])->name('design.submit');
    Route::post('/design/{item}/approve', [DesignController::class, 'approve'])->name('design.approve');
    Route::post('/design/{item}/handoff-to-production', [DesignController::class, 'handoff'])->name('design.handoff');
    Route::post('/design/{item}/change-request', [DesignController::class, 'changeRequest'])->name('design.change_request');
    Route::post('/design/change-request/{changeRequest}/approve', [DesignController::class, 'approveChangeRequest'])->name('design.change_request.approve');
    Route::post('/design/change-request/{changeRequest}/reject', [DesignController::class, 'rejectChangeRequest'])->name('design.change_request.reject');

    Route::get('/design/file/{file}/download', [DesignController::class, 'download'])->name('design.file.download');

    Route::get('/production/queue', [ProductionQueueController::class, 'index'])->name('production.index');
    Route::get('/production/reprints', [ProductionReprintController::class, 'index'])->name('production.reprints.index');
    Route::post('/production/reprints/{reprintRequest}/approve', [ProductionReprintController::class, 'approve'])->name('production.reprints.approve');
    Route::post('/production/reprints/{reprintRequest}/reject', [ProductionReprintController::class, 'reject'])->name('production.reprints.reject');
    Route::get('/production/{item}', [ProductionJobController::class, 'show'])->name('production.show');
    Route::post('/production/{item}/assign', [ProductionJobController::class, 'assign'])->name('production.assign');
    Route::post('/production/{item}/claim', [ProductionJobController::class, 'claim'])->name('production.claim');
    Route::post('/production/{item}/progress', [ProductionJobController::class, 'progress'])->name('production.progress');
    Route::post('/production/{item}/handover', [ProductionJobController::class, 'handover'])->name('production.handover');
    Route::post('/production/{item}/reprint', [ProductionJobController::class, 'requestReprint'])->name('production.reprint.request');

    Route::get('/finishing/queue', [FinishingQueueController::class, 'index'])->name('finishing.index');
    Route::get('/finishing/{item}', [FinishingJobController::class, 'show'])->name('finishing.show');
    Route::post('/finishing/{item}/intake', [FinishingQueueController::class, 'intake'])->name('finishing.intake');
    Route::post('/finishing/{item}/assign', [FinishingJobController::class, 'assign'])->name('finishing.assign');
    Route::post('/finishing/{item}/claim', [FinishingJobController::class, 'claim'])->name('finishing.claim');
    Route::post('/finishing/{item}/progress', [FinishingJobController::class, 'progress'])->name('finishing.progress');
    Route::post('/finishing/{item}/qc', [FinishingJobController::class, 'qc'])->name('finishing.qc');
    Route::post('/finishing/{item}/rework/{rework}/start', [FinishingJobController::class, 'startRework'])->name('finishing.rework.start');
    Route::post('/finishing/{item}/rework/{rework}/complete', [FinishingJobController::class, 'completeRework'])->name('finishing.rework.complete');
    Route::post('/finishing/{item}/packing/start', [FinishingJobController::class, 'startPacking'])->name('finishing.packing.start');
    Route::post('/finishing/{item}/packing/{packing}/complete', [FinishingJobController::class, 'completePacking'])->name('finishing.packing.complete');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('/deliveries/proofs/{proof}/download', [DeliveryController::class, 'downloadProof'])->name('deliveries.proof.download');
    Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
    Route::post('/deliveries/{delivery}/crew', [DeliveryController::class, 'assignCrew'])->name('deliveries.crew');
    Route::post('/deliveries/{delivery}/progress', [DeliveryController::class, 'progress'])->name('deliveries.progress');
    Route::post('/deliveries/{delivery}/proof', [DeliveryController::class, 'proof'])->name('deliveries.proof');
    Route::post('/deliveries/{delivery}/complete', [DeliveryController::class, 'complete'])->name('deliveries.complete');
    Route::get('/deliveries/{delivery}/print', [DeliveryController::class, 'print'])->name('deliveries.print');

    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'update'])->name('notifications.update');

    Route::prefix('system')->name('system.')->group(function () {
        Route::resource('divisions', DivisionController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::get('users/{user}/reset-password', [UserController::class, 'editPassword'])->name('users.edit-password');
        Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('work-calendars', WorkCalendarController::class)->except('show');
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
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
