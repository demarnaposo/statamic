<?php

namespace Modules\Assessment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Assessment\Application\AttemptStore;
use Modules\Assessment\Application\PendingClaim;

final class ClaimController
{
    /** GET /portal/assessment/claim?token=… (tautan dari halaman hasil assessment di landing). */
    public function __invoke(Request $request, PendingClaim $pending, AttemptStore $attempts): RedirectResponse
    {
        $token = $request->query('token');
        $customerId = $request->user('customer')?->getAuthIdentifier();

        if (! PendingClaim::isWellFormed($token)) {
            return redirect()->route($customerId ? 'portal.dashboard' : 'portal.register');
        }

        if (! $customerId) {
            $pending->remember($token);

            return redirect()->route('portal.register');
        }

        if ($attempts->claim($token, (string) $customerId)) {
            session()->flash('assessment_claimed', true);
        }

        return redirect()->route('portal.dashboard');
    }
}
