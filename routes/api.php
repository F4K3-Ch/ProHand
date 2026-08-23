<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TaskerController;
use App\Http\Controllers\NotificationController;

Route::middleware(['web', 'preventAccess', 'auth'])->group(function () {
    Route::get('/profile/data', [ProfileController::class, 'data']);
    Route::post('/profile/update', [ProfileController::class, 'update']);
    Route::post('/profile/update-password', [ProfileController::class, 'updatePassword']);
    Route::get('/notifications', [NotificationController::class, 'index']);

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
    });
});