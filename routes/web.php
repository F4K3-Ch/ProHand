<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Client Routes
    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
        Route::get('/client/create', [ClientController::class, 'create'])->name('client.create');
        Route::post('/client/create', [ClientController::class, 'store'])->name('errand.store');
    });
    
    // Admin Routes
    Route::middleware('role:admin')->get('/admin/dashboard', fn() => view('admin.admin_dashboard'))->name('admin.dashboard');
    
    // Tasker Routes
    Route::middleware('role:tasker')->get('/tasker/dashboard', fn() => view('tasker.tasker_dashboard'))->name('tasker.dashboard');
});