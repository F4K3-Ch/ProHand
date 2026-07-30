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

Route::get('/client/finished', function () {
    return view('client.client_finished_errands');
})->name('client.finished');

Route::get('/tasker/dashboard', function () {
    return view('tasker.tasker_dashboard');
})->name('tasker.dashboard');

Route::get('/admin/profile', function () {
    return view('shared.profile');
})->name('admin.profile');

Route::get('/tasker/profile', function () {
    return view('shared.profile');
})->name('tasker.profile');

Route::get('/client/profile', function () {
    return view('shared.profile');
})->name('client.profile');