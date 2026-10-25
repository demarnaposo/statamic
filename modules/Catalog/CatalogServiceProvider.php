<?php

namespace Modules\Catalog;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Catalog\Application\CachedCatalogQuery;
use Modules\Catalog\Console\InstallCommand;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Contracts\ContentCache;
use Modules\Catalog\Contracts\Data;
use Modules\Catalog\Contracts\SeatInventory;
use Modules\Catalog\Domain\Today;
use Modules\Catalog\Infrastructure\NullSeatInventory;
use Modules\Catalog\Infrastructure\VersionedContentCache;
use Modules\Catalog\Install\CatalogContentInstaller;
use Modules\Catalog\Listeners\ContentChangedSubscriber;

final class CatalogServiceProvider extends ServiceProvider
{
    public const INSTALLER_TAG = 'catalog.content-installers';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'catalog');

        $this->app->bind(Today::class, fn () => new Today((string) config('catalog.timezone')));

        // Modul Commerce nanti cukup mengikat ulang SeatInventory.
        $this->app->bindIf(SeatInventory::class, NullSeatInventory::class);

        $this->app->bind(ContentCache::class, fn ($app) => new VersionedContentCache(
            $app['cache']->store(),
            $app->make(Today::class),
            (int) config('catalog.cache_ttl'),
        ));

        $this->app->bind(CatalogQuery::class, CachedCatalogQuery::class);

        $this->app->tag([CatalogContentInstaller::class], self::INSTALLER_TAG);

        // Laravel 13 menolak unserialize objek dari cache kecuali di-whitelist.
        // DTO kontrak berupa readonly class tanpa magic method, aman di-unserialize.
        $this->allowCacheSerialization([
            Data\BatchDetail::class, Data\BatchSummary::class, Data\Category::class, Data\Faq::class, Data\ProgramDetail::class,
            Data\ProgramSummary::class, Data\Site::class, Data\Testimonial::class,
        ]);
    }

    /** @param  list<class-string>  $classes */
    private function allowCacheSerialization(array $classes): void
    {
        $current = config('cache.serializable_classes');

        if ($current !== true) {
            config(['cache.serializable_classes' => array_values(array_unique(array_merge((array) ($current ?: []), $classes)))]);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('catalog-api', fn (Request $request) => Limit::perMinute((int) config('catalog.rate_limit'))
            ->by($request->ip()));

        Route::prefix('api/v1')
            ->middleware(['api', 'throttle:catalog-api'])
            ->name('api.v1.catalog.')
            ->group(__DIR__.'/routes.php');

        Event::subscribe(ContentChangedSubscriber::class);

        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}
