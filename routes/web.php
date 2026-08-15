<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/login', 'auth.login')->name('login');

Route::view('/register', 'auth.register')->name('register');

Route::view('/admin/dashboard', 'admin.admin_dashboard')->name('admin.dashboard');

Route::view('/tasker/dashboard', 'tasker.tasker_dashboard')->name('tasker.dashboard');

Route::view('/client/dashboard', 'client.client_dashboard')->name('client.dashboard');

Route::get('/client/view-errand/{id}', function ($id) {
    return view('client.view_errand_info', compact('id'));
})->name('client.view.errand');

Route::view('/client/create-errand', 'client.create_errand')->name('client.create.errand');

Route::view('/client/finished', 'client.client_finished_errands')->name('client.finished');

Route::view('/admin/profile', 'shared.profile')->name('admin.profile');

Route::view('/tasker/profile', 'shared.profile')->name('tasker.profile');

Route::view('/client/profile', 'shared.profile')->name('client.profile');