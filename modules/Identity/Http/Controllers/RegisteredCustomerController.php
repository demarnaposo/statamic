<?php

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\Events\CustomerRegistered;
use Modules\Identity\Domain\Customer;
use Modules\Identity\Http\Requests\RegisterRequest;

final class RegisteredCustomerController
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $customer = Customer::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => $request->validated('password'),
            'privacy_accepted_at' => now(),
            'marketing_opt_in_at' => $request->boolean('marketing_opt_in') ? now() : null,
        ]);

        $customer->sendEmailVerificationNotification();

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        event(new CustomerRegistered($customer->id));

        return redirect()->route('portal.verification.notice');
    }
}
