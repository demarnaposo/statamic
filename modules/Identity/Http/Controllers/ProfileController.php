<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Domain\Customer;
use Modules\Identity\Http\Requests\PasswordUpdateRequest;
use Modules\Identity\Http\Requests\ProfileRequest;

final class ProfileController
{
    /** Satu-satunya halaman yang mengirim NPWP ke browser. */
    public function edit(Request $request): Response
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company_name' => $customer->company_name,
                'npwp' => $customer->npwp,
                'billing_address' => $customer->billing_address,
                'marketing_opt_in' => $customer->marketing_opt_in_at !== null,
                'privacy_accepted_at' => $customer->privacy_accepted_at->toIso8601String(),
            ],
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        $data = $request->validated();
        $optIn = (bool) ($data['marketing_opt_in'] ?? false);
        unset($data['marketing_opt_in']);

        $customer->fill($data);
        $customer->marketing_opt_in_at = $optIn ? ($customer->marketing_opt_in_at ?? now()) : null;

        $emailChanged = $customer->isDirty('email');
        if ($emailChanged) {
            $customer->email_verified_at = null;
        }

        $customer->save();

        if ($emailChanged) {
            $customer->sendEmailVerificationNotification();

            return redirect()->route('portal.verification.notice')
                ->with('status', 'Profil disimpan. Verifikasi email baru Anda untuk melanjutkan.');
        }

        return back()->with('status', 'Profil berhasil disimpan.');
    }

    public function password(PasswordUpdateRequest $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');
        // Token remember-me baru + middleware auth.session (hash sandi di session) → sesi di perangkat lain berakhir.
        $customer->forceFill(['password' => $request->validated('password'), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Kata sandi berhasil diubah.');
    }
}
