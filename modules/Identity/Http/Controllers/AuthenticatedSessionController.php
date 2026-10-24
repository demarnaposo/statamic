<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\Events\CustomerAuthenticated;
use Modules\Identity\Http\Requests\LoginRequest;

final class AuthenticatedSessionController
{
    use RedirectsWithinPortal;

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        event(new CustomerAuthenticated((string) Auth::guard('customer')->id()));

        return $this->redirectToIntended();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login')->with('status', 'Anda telah keluar.');
    }
}
