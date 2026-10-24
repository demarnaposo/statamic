<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmailVerificationController
{
    public function notice(Request $request): RedirectResponse|Response
    {
        return $request->user('customer')->hasVerifiedEmail()
            ? redirect()->route('portal.dashboard')
            : Inertia::render('Auth/VerifyEmail', ['email' => $request->user('customer')->email]);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return redirect()->route('portal.dashboard')->with('status', 'Email Anda berhasil diverifikasi.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        if ($customer->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        $customer->sendEmailVerificationNotification();

        return back()->with('status', 'Tautan verifikasi baru telah dikirim ke email Anda.');
    }
}
