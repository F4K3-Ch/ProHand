<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::view('/', 'welcome');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

Route::view('/admin/dashboard', 'admin.admin_dashboard')->name('admin.dashboard');

Route::view('/tasker/dashboard', 'tasker.tasker_dashboard')->name('tasker.dashboard');

Route::view('/client/dashboard', 'client.client_dashboard')->name('client.dashboard');

Route::view('/client/create-errand', 'client.create_errand')->name('client.create.errand');

Route::view('/client/finished', 'client.client_finished_errands')->name('client.finished');

Route::view('/admin/profile', 'shared.profile')->name('admin.profile');

Route::view('/tasker/profile', 'shared.profile')->name('tasker.profile');

Route::view('/client/profile', 'shared.profile')->name('client.profile');