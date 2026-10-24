<?php

namespace Modules\Portal;

use App\Support\PortalRoutes;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\Portal\Http\Middleware\HandleInertiaRequests;

/**
 * Kerangka UI portal customer (Vue 3 + Inertia). Komposisi halaman dari modul lain,
 * hanya melalui `Contracts` modul Identity, Assessment, dan Catalog.
 */
final class PortalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Halaman Inertia portal (dipakai assertInertia saat test).
        config(['inertia.pages.paths' => array_values(array_unique([
            ...(array) config('inertia.pages.paths', []),
            resource_path('js/portal/pages'),
        ]))]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'portal');

        // Group `portal` = web + Inertia portal (+ middleware yang di-push modul lain, mis. Assessment).
        // Prepend, bukan middlewareGroup(), agar tidak bergantung urutan boot provider.
        $router = $this->app->make(Router::class);
        $router->prependMiddlewareToGroup('portal', HandleInertiaRequests::class);
        $router->prependMiddlewareToGroup('portal', 'web');

        PortalRoutes::group(__DIR__.'/routes.php');
    }
}
