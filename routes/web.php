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

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', function () {
        return view('shared.profile');
    })->name('profile');

    Route::get('/profile/edit', function () {
        return view('shared.edit_profile');
    })->name('profile.edit');

    Route::get('/profile/change-password', function () {
        return view('shared.change_password');
    })->name('profile.change-password');

    Route::get('/notifications', function () {
        return view('shared.notifications');
    })->name('notifications');

    // Client dashboard
    Route::middleware('role:client')->group(function () {
        Route::get('/client/dashboard', function () {
            return view('client.client_dashboard');
        })->name('client.dashboard');

        Route::get('/client/create-errand', function () {
            return view('client.create_errand');
        })->name('client.create.errand');

        Route::get('/client/view-errand/{id}', function ($id) {
            return view('client.view_errand_info', ['id' => $id]);
        })->name('client.view-errand');

        Route::get('/client/edit-errand/{id}', function ($id) {
            return view('client.edit_errand', ['id' => $id]);
        })->name('client.edit-errand');
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

        Route::get('/admin/view-errand/{id}', function ($id) {
            return view('admin.view_errand_info', ['id' => $id]);
        })->name('admin.view-errand');
    });
});