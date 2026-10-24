<?php

use App\Http\Middleware\NoStore;
use Illuminate\Support\Facades\Route;
use Modules\Assessment\Http\Controllers\AssessmentController;

// Didaftarkan oleh AssessmentServiceProvider dengan prefix `api/v1`.

Route::get('assessment', [AssessmentController::class, 'show'])->name('show');
Route::post('assessment/score', [AssessmentController::class, 'score'])->middleware(NoStore::class)->name('score');
