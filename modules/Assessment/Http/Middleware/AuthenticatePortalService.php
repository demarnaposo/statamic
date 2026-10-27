<?php

namespace Modules\Assessment\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Endpoint internal: hanya untuk portal dengan `Authorization: Bearer <PORTAL_SERVICE_TOKEN>`. */
final class AuthenticatePortalService
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.portal.token');
        $given = (string) $request->bearerToken();

        // Token kosong di server = endpoint dimatikan (bukan "token kosong cocok").
        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Tidak diizinkan.'], 401);
        }

        return $next($request);
    }
}
