<?php

namespace Modules\Assessment;

use App\Support\PortalRoutes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Assessment\Application\AssessmentDefinition;
use Modules\Assessment\Application\AttemptStore;
use Modules\Assessment\Contracts\AssessmentResults;
use Modules\Assessment\Http\Middleware\CapturePendingClaim;
use Modules\Assessment\Install\AssessmentContentInstaller;
use Modules\Assessment\Listeners\ClaimPendingAssessment;
use Modules\Identity\Contracts\Events\CustomerAuthenticated;
use Modules\Identity\Contracts\Events\CustomerRegistered;

/** Bergantung pada modul Catalog & Identity hanya melalui `Contracts` masing-masing. */
final class AssessmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([AssessmentContentInstaller::class], 'catalog.content-installers');
        $this->app->bind(AssessmentResults::class, AttemptStore::class);

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

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        // Klaim hasil anonim ke akun: ?assessment=<token> di URL portal + saat login/daftar.
        $this->app->make(Router::class)->pushMiddlewareToGroup('portal', CapturePendingClaim::class);
        Event::listen([CustomerRegistered::class, CustomerAuthenticated::class], ClaimPendingAssessment::class);
        PortalRoutes::group(__DIR__.'/portal-routes.php');
    }
}
