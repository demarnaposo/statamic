<?php

use Illuminate\Support\Str;

// Portal customer (modules/Identity, modules/Portal, route portal di modul lain).
return [
    // Kosong = host mana pun (lokal). Produksi: mis. app.sertifika.id → Route::domain().
    'domain' => env('PORTAL_DOMAIN') ?: null,

    // Prefix path semua route portal (`/portal/...`). Boleh dikosongkan bila memakai PORTAL_DOMAIN.
    'prefix' => trim((string) env('PORTAL_PREFIX', 'portal'), '/'),

    // Cookie session portal (path `/<prefix>`), terpisah dari cookie session CP; lihat PortalSessionCookie.
    'session_cookie' => Str::slug((string) env('APP_NAME', 'laravel')).'-portal-session',

    // Landing Astro (katalog publik), dipakai untuk tautan "Lihat katalog".
    'landing_url' => rtrim((string) env('LANDING_URL', 'http://localhost:4321'), '/'),
];
