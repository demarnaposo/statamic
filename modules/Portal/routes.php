<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Portal\Http\Controllers\AssessmentHistoryController;
use Modules\Portal\Http\Controllers\CheckoutController;
use Modules\Portal\Http\Controllers\DashboardController;

// Didaftarkan lewat App\Support\PortalRoutes (prefix `portal`, nama `portal.`, middleware `portal`).

Route::get('/', fn () => redirect()->route('portal.dashboard'))->name('home');

Route::middleware(['auth:customer', 'auth.session', 'verified:portal.verification.notice'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('assessments', AssessmentHistoryController::class)->name('assessments');
    Route::get('checkout', CheckoutController::class)->name('checkout');

    // Segera hadir: modul Commerce (pesanan) dan Learner (program saya).
    Route::get('orders', fn () => Inertia::render('ComingSoon', ['section' => 'orders']))->name('orders');
    Route::get('programs', fn () => Inertia::render('ComingSoon', ['section' => 'programs']))->name('programs');
});
