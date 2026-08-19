<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'preventAccess', 'auth'])->group(function () {
    Route::get('/profile/data', [ProfileController::class, 'data']);

    Route::get('/client/dashboard/active-metrics', [ClientController::class, 'activeMetrics']);
    Route::get('/client/dashboard/active-errands', [ClientController::class, 'activeErrands']);
    Route::post('/client/create', [ClientController::class, 'createErrand']);
    Route::get('/client/errands/{id}', [ClientController::class, 'getErrandDetails']);
    Route::post('/client/errands/{id}/update', [ClientController::class, 'editErrand']);
});