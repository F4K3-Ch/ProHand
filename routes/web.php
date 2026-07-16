<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Auth Routes
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});

// Dashboards (Protected)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.admin_dashboard');
    })->name('admin.dashboard');
});

Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/client/dashboard', function () {
        return view('client.client_dashboard');
    })->name('client.dashboard');
});

Route::middleware(['auth', 'role:tasker'])->group(function () {
    Route::get('/tasker/dashboard', function () {
        return view('tasker.tasker_dashboard');
    })->name('tasker.dashboard');
});
