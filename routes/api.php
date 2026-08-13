<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ErrandController;

Route::middleware(['web', 'preventAccess'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');

    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::get('/errands/client', [ErrandController::class, 'clientErrands'])->name('api.client.errands');
});