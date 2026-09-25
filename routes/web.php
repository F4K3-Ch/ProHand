<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->role . '.dashboard');
    }

    return view('welcome');
});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    if (!URL::hasValidSignature($request)) {
        abort(403, 'Invalid or expired verification link.');
    }

    $user = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Invalid verification link.');
    }

    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect()->route('login', [
        'verified' => '1'
    ]);
})->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->validate([
        'email' => ['required', 'email', 'max:100'],
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Email is not registered.'
        ], 404);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'success' => false,
            'message' => 'This email is already verified.'
        ], 400);
    }

    $user->sendEmailVerificationNotification();

    return response()->json([
        'success' => true,
        'message' => 'A new verification link has been sent to your email.'
    ]);
})->middleware(['preventAccess', 'throttle:6,1'])->name('verification.send');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

Route::middleware(['preventAccess'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware(['auth', 'preventBackHistory'])->group(function () {

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

        Route::get('/client/history', function () {
            return view('client.client_history');
        })->name('client.history');

        Route::get('/client/create-errand', function () {
            return view('client.create_errand');
        })->name('client.create-errand');

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

        Route::get('/tasker/available-errands', function () {
            return view('tasker.available_errands');
        })->name('tasker.available-errands');

        Route::get('/tasker/history', function () {
            return view('tasker.tasker_history');
        })->name('tasker.history');

        Route::get('/tasker/view-errand/{id}', function ($id) {
            return view('tasker.view_errand_info', ['id' => $id]);
        })->name('tasker.view-errand');
    });

    // Admin dashboard
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return view('admin.admin_dashboard');
        })->name('admin.dashboard');

        Route::get('/admin/history', function () {
            return view('admin.admin_history');
        })->name('admin.history');

        Route::get('/admin/view-errand/{id}', function ($id) {
            return view('admin.view_errand_info', ['id' => $id]);
        })->name('admin.view-errand');

        Route::get('/admin/manage-users', function () {
            return view('admin.manage_users');
        })->name('admin.manage-users');

        Route::get('/admin/view-user/{id}', function ($id) {
            return view('admin.view_user_info', ['id' => $id]);
        })->name('admin.view-user');

        Route::get('/admin/add-user', function () {
            return view('admin.add_user');
        })->name('admin.add-user');
    });
});