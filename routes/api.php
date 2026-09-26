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
    Route::post('/profile/delete-account', [ProfileController::class, 'deleteAccount']);
    
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/delete', [NotificationController::class, 'destroy']);
    Route::post('/notifications/delete-all', [NotificationController::class, 'destroyAll']);

    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard/active-metrics', [ClientController::class, 'activeMetrics']);
        Route::get('/client/dashboard/active-errands', [ClientController::class, 'activeErrands']);
        Route::get('/client/history/history-metrics', [ClientController::class, 'historyMetrics']);
        Route::get('/client/history/history-errands', [ClientController::class, 'historyErrands']);
        Route::post('/client/create', [ClientController::class, 'createErrand']);
        Route::get('/client/errands/{id}', [ClientController::class, 'getErrandDetails']);
        Route::post('/client/errands/{id}/payment-proof', [ClientController::class, 'submitPaymentProof']);
        Route::post('/client/errands/{id}/update', [ClientController::class, 'editErrand']);
        Route::post('/client/errands/{id}/delete', [ClientController::class, 'destroyErrand']);
        Route::post('/client/errands/{id}/dispute', [ClientController::class, 'markDispute']);
        Route::post('/client/errands/{id}/completed', [ClientController::class, 'confirmCompletion']);
        Route::post('/client/errands/{id}/cancel', [ClientController::class, 'cancelErrand']);
        Route::post('/client/errands/{id}/request-cancel', [ClientController::class, 'requestCancellation']);
        Route::post('/client/history/{id}/delete', [ClientController::class, 'destroyHistory']);
    });

    Route::middleware('role:tasker')->group(function () {
        Route::get('/tasker/dashboard/accepted-metrics', [TaskerController::class, 'acceptedMetrics']);
        Route::get('/tasker/dashboard/accepted-errands', [TaskerController::class, 'acceptedErrands']);
        Route::get('/tasker/available-errands/pending-metrics', [TaskerController::class, 'pendingMetrics']);
        Route::get('/tasker/available-errands/pending-errands', [TaskerController::class, 'pendingErrands']);
        Route::get('/tasker/history/history-metrics', [TaskerController::class, 'historyMetrics']);
        Route::get('/tasker/history/history-errands', [TaskerController::class, 'historyErrands']);
        Route::get('/tasker/errands/{id}', [TaskerController::class, 'getErrandDetails']);
        Route::post('/tasker/errands/{id}/accept', [TaskerController::class, 'acceptErrand']);
        Route::post('/tasker/errands/{id}/awaiting-confirmation', [TaskerController::class, 'markAwaitingConfirmation']);
        Route::post('/tasker/errands/{id}/confirm-cash', [TaskerController::class, 'confirmCashReceived']);
        Route::post('/tasker/errands/{id}/request-cancel', [TaskerController::class, 'requestCancellation']);
        Route::post('/tasker/history/{id}/delete', [TaskerController::class, 'destroyHistory']);
    });

    Route::middleware('role:admin')->group(function() {
        Route::get('/admin/dashboard/active-metrics', [AdminController::class, 'allActiveMetrics']);
        Route::get('/admin/dashboard/active-errands', [AdminController::class, 'allActiveErrands']);
        Route::get('/admin/history/history-metrics', [AdminController::class, 'allHistoryMetrics']);
        Route::get('/admin/history/history-errands', [AdminController::class, 'allHistoryErrands']);
        Route::get('/admin/errands/{id}', [AdminController::class, 'getErrandDetails']);
        Route::post('/admin/errands/{id}/accept-receipt', [AdminController::class, 'acceptReceipt']);
        Route::post('/admin/errands/{id}/deny-receipt', [AdminController::class, 'denyReceipt']);
        Route::post('/admin/errands/{id}/reset-to-pending', [AdminController::class, 'resetErrandToPending']);
        Route::post('/admin/errands/{id}/approve-cancellation', [AdminController::class, 'approveCancellation']);
        Route::post('/admin/errands/{id}/deny-cancellation', [AdminController::class, 'denyCancellation']);
        Route::post('/admin/errands/{id}/delete', [AdminController::class, 'destroyErrand']);
        Route::post('/admin/errands/{id}/completed', [AdminController::class, 'updateCompleted']);
        Route::post('/admin/errands/{id}/failed', [AdminController::class, 'updateFailed']);
        Route::post('/admin/history/{id}/delete', [AdminController::class, 'deleteHistory']);
        Route::get('/admin/users-metrics', [AdminController::class, 'usersMetrics']);
        Route::get('/admin/users', [AdminController::class, 'allUsers']);
        Route::post('/admin/add-user', [AdminController::class, 'storeUser']);
        Route::get('/admin/users/{id}', [AdminController::class, 'showUserData']);
    });
});