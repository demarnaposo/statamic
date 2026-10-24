<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\AuthenticatedSessionController;
use Modules\Identity\Http\Controllers\EmailVerificationController;
use Modules\Identity\Http\Controllers\PasswordResetController;
use Modules\Identity\Http\Controllers\ProfileController;
use Modules\Identity\Http\Controllers\RegisteredCustomerController;

// Didaftarkan lewat App\Support\PortalRoutes (prefix `portal`, nama `portal.`, middleware `portal`).

Route::middleware('guest:customer')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:portal-login')->name('login.store');

    Route::get('register', [RegisteredCustomerController::class, 'create'])->name('register');
    Route::post('register', [RegisteredCustomerController::class, 'store'])->middleware('throttle:portal-register')->name('register.store');

    Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:portal-password')->name('password.email');
    Route::get('reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:portal-password')->name('password.store');
});

Route::middleware(['auth:customer', 'auth.session'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:portal-verification'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:portal-verification')->name('verification.send');

    Route::middleware('verified:portal.verification.notice')->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('password', [ProfileController::class, 'password'])->name('password.update');
    });
});
