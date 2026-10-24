<?php

namespace Modules\Portal\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Modules\Identity\Contracts\CustomerDirectory;

/**
 * Middleware Inertia khusus grup route `portal` (root view & aset Vite sendiri).
 * CP Statamic memakai Statamic\Http\Middleware\CP\HandleInertiaRequests di grup route-nya sendiri.
 */
final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'portal::app';

    public function __construct(private readonly CustomerDirectory $customers) {}

    public function version(Request $request): ?string
    {
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? hash_file('xxh128', $manifest) : null;
    }

    public function share(Request $request): array
    {
        $customer = $this->customers->current();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('mail.from.name') ?: config('app.name'),
                'landing_url' => config('portal.landing_url'),
            ],
            // Sengaja hanya field non-sensitif (tanpa NPWP/alamat); profil lengkap hanya di halaman profil.
            'auth' => ['customer' => $customer ? [
                'name' => $customer->name,
                'email' => $customer->email,
                'email_verified' => $customer->emailVerified,
            ] : null],
            'flash' => ['status' => fn () => $request->session()->get('status')],
            'routes' => fn () => $this->routes(),
        ];
    }

    /** URL route portal untuk navigasi & form (prefix/domain bisa berubah lewat env). */
    private function routes(): array
    {
        $names = [
            'home', 'dashboard', 'assessments', 'orders', 'programs', 'profile.edit', 'profile.update',
            'password.update', 'logout', 'login', 'register', 'password.request', 'password.email',
            'password.store', 'verification.send', 'login.store', 'register.store',
        ];

        return collect($names)->mapWithKeys(fn ($n) => [$n => route('portal.'.$n)])->all();
    }
}
