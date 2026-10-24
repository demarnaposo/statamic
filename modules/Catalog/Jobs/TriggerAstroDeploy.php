<?php

namespace Modules\Catalog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * POST ke ASTRO_DEPLOY_HOOK_URL. Jika receiver mati/menolak, job gagal dengan log
 * peringatan lalu di-retry oleh worker dengan backoff; setelah percobaan terakhir
 * dicatat sebagai error (tabel failed_jobs). Worker tidak ikut crash.
 */
final class TriggerAstroDeploy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    /** @return list<int> jeda (detik) sebelum percobaan ke-2, ke-3, dst. */
    public function backoff(): array
    {
        return [30, 60, 120, 300];
    }

    public function handle(): void
    {
        $url = config('catalog.astro.deploy_hook_url');

        if (! filled($url)) {
            return;
        }

        try {
            Http::timeout(10)
                ->acceptJson()
                ->post($url, ['source' => 'statamic', 'triggered_at' => now()->toIso8601String()])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('Astro deploy hook gagal; akan dicoba ulang.', $this->context($url) + [
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::info('Astro deploy hook dipicu.', $this->context($url) + ['attempt' => $this->attempts()]);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('Astro deploy hook gagal permanen setelah semua percobaan.', $this->context((string) config('catalog.astro.deploy_hook_url')) + [
            'error' => $e?->getMessage(),
        ]);
    }

    /** Host + path saja: query string (token) tidak ditulis ke log. */
    private function context(string $url): array
    {
        $parts = parse_url($url) ?: [];

        return ['hook' => ($parts['host'] ?? '?').(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '')];
    }
}
