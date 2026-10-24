<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Domain\Customer;
use Modules\Identity\Http\Requests\ForgotPasswordRequest;
use Modules\Identity\Http\Requests\ResetPasswordRequest;

final class PasswordResetController
{
    public function request(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::broker('customers')->sendResetLink($request->only('email'));

        // Pesan sama untuk semua hasil (terkirim, email tak dikenal, throttle) agar tidak membocorkan akun terdaftar.
        return back()->with('status', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi telah dikirim.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                event(new PasswordReset($customer));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => $status === Password::RESET_THROTTLED
                    ? 'Mohon tunggu sebelum mencoba lagi.'
                    : 'Tautan atur ulang kata sandi tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        return redirect()->route('portal.login')->with('status', 'Kata sandi berhasil diubah. Silakan masuk.');
    }
}
