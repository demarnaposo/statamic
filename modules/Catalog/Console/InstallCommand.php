<?php

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Modules\Catalog\CatalogServiceProvider;
use Modules\Catalog\Contracts\ContentCache;
use Modules\Catalog\Contracts\ContentInstaller;
use Modules\Catalog\Install\CatalogContentInstaller;

final class InstallCommand extends Command
{
    protected $signature = 'catalog:install
        {--demo : Isi data demo (kategori, program, batch, testimoni, FAQ, global, asesmen)}
        {--force : Timpa blueprint & data demo yang sudah ada}';

    protected $description = 'Membuat model konten Statamic untuk katalog sertifikasi (idempoten).';

    public function handle(ContentCache $cache): int
    {
        // Jangan memicu deploy hook Astro untuk setiap item yang dibuat.
        config(['catalog.astro.deploy_hook_url' => null]);

        $force = (bool) $this->option('force');
        $installers = $this->installers();

        $this->components->info('Struktur konten');
        foreach ($installers as $installer) {
            $installer->installStructure($this->output, $force);
        }

        if ($this->option('demo')) {
            $this->components->info('Data demo');
            foreach ($installers as $installer) {
                $installer->installDemo($this->output, $force);
            }
        }

        $cache->flush();
        $this->components->info('Selesai. Cache katalog di-reset.');

        return self::SUCCESS;
    }

    /** @return list<ContentInstaller> Catalog dulu (taxonomy kategori dipakai modul lain). */
    private function installers(): array
    {
        $tagged = iterator_to_array($this->laravel->tagged(CatalogServiceProvider::INSTALLER_TAG), false);

        usort($tagged, fn ($a, $b) => ($b instanceof CatalogContentInstaller) <=> ($a instanceof CatalogContentInstaller));

        return $tagged;
    }
}
