<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

Route::get('/admin/dashboard', function () {
    return view('admin.admin_dashboard');
})->name('admin.dashboard');

Route::get('/client/dashboard', function () {
    return view('client.client_dashboard');
})->name('client.dashboard');

Route::get('/tasker/dashboard', function () {
    return view('tasker.tasker_dashboard');
})->name('tasker.dashboard');