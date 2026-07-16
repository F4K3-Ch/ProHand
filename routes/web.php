<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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


Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:client')->get('/client/dashboard', [ClientController::class, 'dashboard'])->name('client.dashboard');
    
    Route::middleware('role:admin')->get('/admin/dashboard', fn() => view('admin.admin_dashboard'))->name('admin.dashboard');
    
    Route::middleware('role:tasker')->get('/tasker/dashboard', fn() => view('tasker.tasker_dashboard'))->name('tasker.dashboard');
});
