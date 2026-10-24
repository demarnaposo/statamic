<?php

namespace Modules\Catalog\Application;

use Illuminate\Contracts\Cache\Repository;
use Modules\Catalog\Jobs\TriggerAstroDeploy;

/**
 * Menjadwalkan POST ke ASTRO_DEPLOY_HOOK_URL dengan debounce memakai cache lock.
 *
 * - Queue async (database/redis): perubahan pertama mengambil lock selama N detik
 *   dan men-dispatch job dengan delay N detik. Perubahan berikutnya dalam jendela
 *   itu diabaikan — job yang berjalan di akhir jendela sudah mencakup semuanya.
 * - Queue `sync`: delay tidak didukung, job dijalankan setelah respons dikirim
 *   (afterResponse) dan lock mencegah build ganda selama N detik.
 */
final class AstroDeployHook
{
    public const LOCK_KEY = 'catalog:astro-deploy:debounce';

    public function __construct(private readonly Repository $cache) {}

    public function schedule(): bool
    {
        if (! filled(config('catalog.astro.deploy_hook_url'))) {
            return false;
        }

        $seconds = max(1, (int) config('catalog.astro.debounce_seconds', 60));

        if (! $this->cache->getStore()->lock(self::LOCK_KEY, $seconds)->get()) {
            return false;
        }

        if (config('queue.default') === 'sync') {
            dispatch(new TriggerAstroDeploy)->afterResponse();
        } else {
            dispatch(new TriggerAstroDeploy)->delay(now()->addSeconds($seconds));
        }

        return true;
    }
}
