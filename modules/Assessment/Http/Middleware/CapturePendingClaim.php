<?php

namespace Modules\Assessment\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Assessment\Application\AttemptStore;
use Modules\Assessment\Application\PendingClaim;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dipasang di middleware group `portal`: `?assessment=<token>` di URL portal mana pun
 * (mis. /portal/register?assessment=…) disimpan untuk diklaim setelah login/daftar,
 * atau langsung diklaim bila customer sudah login.
 */
final class CapturePendingClaim
{
    public function __construct(
        private readonly PendingClaim $pending,
        private readonly AttemptStore $attempts,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('assessment');

        if (PendingClaim::isWellFormed($token)) {
            $customerId = $request->user('customer')?->getAuthIdentifier();

            if ($customerId && $this->attempts->claim($token, (string) $customerId)) {
                $request->session()->now('assessment_claimed', true);
            } elseif (! $customerId) {
                $this->pending->remember($token);
            }
        }

        Inertia::share('assessment', fn () => [
            'pending' => $this->pending->has(),
            'claimed' => (bool) $request->session()->get('assessment_claimed'),
        ]);

        return $next($request);
    }
}
