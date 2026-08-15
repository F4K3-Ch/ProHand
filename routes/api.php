<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'preventAccess', 'auth'])->group(function () {
    Route::get('/profile/data', [ProfileController::class, 'data'])
        ->name('profile.data');
});