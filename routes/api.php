<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')
        ->prefix('admin')
        ->group(function () {
            Route::get('/dashboard', function () {
                return response()->json([
                    'message' => 'Welcome Admin',
                    'user' => request()->user()
                ]);
            });
        });

    Route::middleware('role:tasker')
        ->prefix('tasker')
        ->group(function () {
            Route::get('/dashboard', function () {
                return response()->json([
                    'message' => 'Welcome Tasker',
                    'user' => request()->user()
                ]);
            });
        });

    Route::middleware('role:client')
        ->prefix('client')
        ->group(function () {
            Route::get('/dashboard', function () {
                return response()->json([
                    'message' => 'Welcome Client',
                    'user' => request()->user()
                ]);
            });
        });
});