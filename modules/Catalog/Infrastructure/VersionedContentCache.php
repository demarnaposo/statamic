<?php

namespace Modules\Catalog\Infrastructure;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\ContentCache;
use Modules\Catalog\Domain\Today;

final class VersionedContentCache implements ContentCache
{
    private const VERSION_KEY = 'catalog:content-version';

    public function __construct(
        private readonly Repository $cache,
        private readonly Today $today,
        private readonly int $ttl,
    ) {}

    /**
     * Kunci menyertakan versi konten dan tanggal hari ini (zona waktu katalog),
     * karena harga efektif/promo/batch mendatang bergantung pada tanggal.
     * Nilai dibungkus array agar hasil `null` (mis. 404) ikut ter-cache.
     */
    public function remember(string $key, Closure $callback): mixed
    {
        $fullKey = sprintf('catalog:v%s:%s:%s', $this->version(), $this->today->date(), $key);

        return $this->cache->remember($fullKey, $this->ttl, fn () => ['value' => $callback()])['value'];
    }

    public function version(): string
    {
        $version = $this->cache->get(self::VERSION_KEY);

        if (! is_string($version) || $version === '') {
            $version = $this->bump();
        }

        return $version;
    }

    public function flush(): void
    {
        $this->bump();
    }

    private function bump(): string
    {
        $version = strtolower((string) Str::ulid());
        $this->cache->forever(self::VERSION_KEY, $version);

        return $version;
    }
}
