<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\HandleCors as BaseHandleCors;
use Illuminate\Http\Request;

/** CORS untuk API publik landing (`api/*`), kecuali endpoint server-to-server `api/v1/internal/*`. */
class HandleCors extends BaseHandleCors
{
    protected function hasMatchingPath(Request $request): bool
    {
        return ! $request->is('api/v1/internal/*') && parent::hasMatchingPath($request);
    }
}
