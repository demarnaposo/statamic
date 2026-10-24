<?php

return [
    // Zona waktu untuk menentukan "hari ini" (promo aktif, batch mendatang).
    'timezone' => env('CATALOG_TIMEZONE', 'Asia/Jakarta'),

    // TTL cache respons build-time (detik). Invalidasi utama tetap via versi konten.
    'cache_ttl' => (int) env('CATALOG_CACHE_TTL', 86400),

    // Batas request API per menit per IP.
    'rate_limit' => (int) env('CATALOG_API_RATE_LIMIT', 120),

    // Konten Statamic yang memicu invalidasi cache + deploy hook saat berubah.
    'watch' => [
        'collections' => ['programs', 'batches', 'testimonials', 'faqs'],
        'taxonomies' => ['categories'],
        'globals' => ['site', 'assessment'],
    ],

    'astro' => [
        'deploy_hook_url' => env('ASTRO_DEPLOY_HOOK_URL'),
        'debounce_seconds' => (int) env('ASTRO_DEPLOY_DEBOUNCE_SECONDS', 60),
    ],
];
