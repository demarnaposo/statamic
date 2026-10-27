<?php

namespace Modules\Assessment;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Assessment\Application\AssessmentDefinition;
use Modules\Assessment\Install\AssessmentContentInstaller;

/**
 * Konten & bobot assessment + scoring. Penyimpanan attempt dan klaim ke akun ada di aplikasi portal,
 * yang memanggil endpoint internal scoring. Bergantung pada Catalog hanya melalui `Contracts`.
 */
final class AssessmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([AssessmentContentInstaller::class], 'catalog.content-installers');

        // Lihat CatalogServiceProvider: whitelist unserialize untuk Laravel 13.
        $current = config('cache.serializable_classes');
        if ($current !== true) {
            config(['cache.serializable_classes' => array_values(array_unique(array_merge((array) ($current ?: []), [AssessmentDefinition::class])))]);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('assessment-api', fn (Request $request) => Limit::perMinute((int) config('catalog.rate_limit', 120))
            ->by($request->ip()));

        Route::prefix('api/v1')
            ->middleware(['api', 'throttle:assessment-api'])
            ->name('api.v1.assessment.')
            ->group(__DIR__.'/routes.php');
    }
}
