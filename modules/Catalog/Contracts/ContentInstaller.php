<?php

namespace Modules\Catalog\Contracts;

use Illuminate\Console\OutputStyle;

/**
 * Installer konten Statamic per modul. Didaftarkan dengan tag
 * `catalog.content-installers` dan dijalankan oleh `php artisan catalog:install`.
 */
interface ContentInstaller
{
    /** Buat taxonomy/collection/global/blueprint. Idempoten. */
    public function installStructure(OutputStyle $output, bool $force = false): void;

    /** Isi data demo. Idempoten: item yang sudah ada dilewati kecuali $force. */
    public function installDemo(OutputStyle $output, bool $force = false): void;
}
