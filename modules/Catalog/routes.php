<?php

use App\Http\Middleware\NoStore;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\AvailabilityController;
use Modules\Catalog\Http\Controllers\BatchController;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\FaqController;
use Modules\Catalog\Http\Controllers\PriceController;
use Modules\Catalog\Http\Controllers\ProgramController;
use Modules\Catalog\Http\Controllers\SiteController;
use Modules\Catalog\Http\Controllers\TestimonialController;

// Didaftarkan oleh CatalogServiceProvider dengan prefix `api/v1`, middleware `api` + `throttle:catalog-api`.

Route::get('site', SiteController::class)->name('site');
Route::get('categories', CategoryController::class)->name('categories');
Route::get('programs', [ProgramController::class, 'index'])->name('programs.index');
Route::get('programs/{slug}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('programs/{slug}/availability', AvailabilityController::class)->middleware(NoStore::class)->name('programs.availability');
Route::get('prices', PriceController::class)->middleware(NoStore::class)->name('prices');
Route::get('batches/{id}', BatchController::class)->middleware(NoStore::class)->name('batches.show');
Route::get('testimonials', TestimonialController::class)->name('testimonials');
Route::get('faqs', FaqController::class)->name('faqs');
