<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global (di-prepend, sebelum StartSession): request portal memakai cookie session sendiri dengan path
 * `/<prefix>`, sehingga session & XSRF-TOKEN portal tidak bertabrakan dengan CP di host yang sama.
 */
class PortalSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = config('portal.domain');
        $prefix = config('portal.prefix');

        $isPortal = ($domain || $prefix)
            && (! $domain || $request->getHost() === $domain)
            && (! $prefix || $request->is($prefix, $prefix.'/*'));

        if ($isPortal) {
            config([
                'session.cookie' => config('portal.session_cookie'),
                'session.path' => '/'.$prefix,
            ]);
            // Store bisa sudah dibuat saat boot (nama dibaca saat itu), jadi ganti juga di instance-nya.
            app('session')->driver()->setName(config('session.cookie'));
        }

        return $next($request);
    }
}
