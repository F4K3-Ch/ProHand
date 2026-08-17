<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')
        ->prefix('admin')
        ->group(function () {

        });

    Route::middleware('role:tasker')
        ->prefix('tasker')
        ->group(function () {

        });

    Route::middleware('role:client')
        ->prefix('client')
        ->group(function () {
            Route::post('/create-errand', [ClientController::class, 'createErrand']);
            Route::get('/active-metrics', [ClientController::class, 'activeMetrics']);
            Route::get('/active-errands', [ClientController::class, 'activeErrands']);
            Route::get('/errands/{id}', [ClientController::class, 'showErrand']);
        });
});