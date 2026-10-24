<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Route;

/**
 * Satu tempat aturan grup route portal (domain/prefix/nama/middleware `portal`), dipakai
 * oleh setiap modul yang menyumbang halaman portal tanpa saling meng-import.
 * Middleware group `portal` (web + Inertia portal) didefinisikan oleh PortalServiceProvider.
 */
final class PortalRoutes
{
    public static function group(Closure|string $routes): void
    {
        $registrar = Route::middleware('portal')->name('portal.');

        if ($domain = config('portal.domain')) {
            $registrar->domain($domain);
        }

        if ($prefix = config('portal.prefix')) {
            $registrar->prefix($prefix);
        }

        $registrar->group($routes);
    }
}
