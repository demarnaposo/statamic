<?php

use App\Http\Middleware\NoStore;
use Illuminate\Support\Facades\Route;
use Modules\Assessment\Http\Controllers\AssessmentController;
use Modules\Assessment\Http\Middleware\AuthenticatePortalService;

// Didaftarkan oleh AssessmentServiceProvider dengan prefix `api/v1`.

Route::get('assessment', [AssessmentController::class, 'show'])->name('show');

// Server-to-server dari portal (Bearer PORTAL_SERVICE_TOKEN), tanpa CORS (App\Http\Middleware\HandleCors).
// Scoring publik untuk browser ada di portal: POST {portal}/api/assessment/score.
Route::post('internal/assessment/score', [AssessmentController::class, 'score'])
    ->middleware([AuthenticatePortalService::class, NoStore::class])
    ->name('internal.score');
