<?php

use Illuminate\Support\Facades\Route;
use Modules\Assessment\Http\Controllers\ClaimController;

// Didaftarkan lewat App\Support\PortalRoutes (prefix `portal`, nama `portal.`).

Route::get('assessment/claim', ClaimController::class)->name('assessment.claim');
