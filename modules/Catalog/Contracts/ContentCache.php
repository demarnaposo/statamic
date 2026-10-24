<?php

namespace Modules\Catalog\Contracts;

use Closure;

/**
 * Cache respons build-time dengan kunci ber-versi. Versi dinaikkan setiap
 * kali konten Statamic berubah, sehingga semua kunci lama otomatis basi.
 */
interface ContentCache
{
    public function remember(string $key, Closure $callback): mixed;

    public function version(): string;

    public function flush(): void;
}
