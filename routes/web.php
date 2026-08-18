<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', function () {
        return view('shared.profile');
    })->name('profile');

    // Client dashboard
    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', function () {
            return view('client.client_dashboard');
        })->name('client.dashboard');

        Route::get('/client/create-errand', function () {
            return view('client.create_errand');
        })->name('client.create.errand');
    });

    // Tasker dashboard
    Route::middleware('role:tasker')->group(function () {
        Route::get('/tasker/dashboard', function () {
            return view('tasker.tasker_dashboard');
        })->name('tasker.dashboard');
    });

    // Admin dashboard
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('admin.admin_dashboard');
        })->name('admin.dashboard');
    });
});