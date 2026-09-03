<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TaskerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'preventAccess', 'auth'])->group(function () {
    Route::get('/profile/data', [ProfileController::class, 'data']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);
    
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);

    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard/active-metrics', [ClientController::class, 'activeMetrics']);
        Route::get('/client/dashboard/active-errands', [ClientController::class, 'activeErrands']);
        Route::post('/client/create', [ClientController::class, 'createErrand']);
        Route::get('/client/errands/{id}', [ClientController::class, 'getErrandDetails']);
        Route::post('/client/errands/{id}/update', [ClientController::class, 'editErrand']);
        Route::post('/client/errands/{id}/delete', [ClientController::class, 'destroyErrand']);
    });

    Route::middleware('role:tasker')->group(function () {
        Route::get('/tasker/dashboard/pending-metrics', [TaskerController::class, 'pendingMetrics']);
        Route::get('/tasker/dashboard/pending-errands', [TaskerController::class, 'pendingErrands']);
        Route::get('/tasker/your-task/accepted-metrics', [TaskerController::class, 'acceptedMetrics']);
        Route::get('/tasker/your-task/accepted-errands', [TaskerController::class, 'acceptedErrands']);
        Route::get('/tasker/errands/{id}', [TaskerController::class, 'getErrandDetails']);
        Route::post('/tasker/errands/{id}/accept', [TaskerController::class, 'acceptErrand']);
        Route::post('/tasker/errands/{id}/start', [TaskerController::class, 'startErrand']);
        Route::post('/tasker/errands/{id}/awaiting-confirmation', [TaskerController::class, 'markAwaitingConfirmation']);
    });

    Route::middleware('role:admin')->group(function() {
        Route::get('/admin/dashboard/active-metrics', [AdminController::class, 'allActiveMetrics']);
        Route::get('/admin/dashboard/active-errands', [AdminController::class, 'allActiveErrands']);
    });
});