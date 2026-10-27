<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Aturan modular monolith: modul hanya boleh memakai `Modules\<Lain>\Contracts\…` milik modul lain.
 */
class ModuleBoundariesTest extends TestCase
{
    private const MODULES = __DIR__.'/../../../modules';

    /** @return array<string, array{string, string, string}> modul => [file, modul tujuan, sisa namespace] */
    private static function references(): array
    {
        $refs = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::MODULES, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen(self::MODULES) + 1);
            $owner = explode(DIRECTORY_SEPARATOR, $relative)[0];

            // Semua bentuk: `use Modules\X\…`, FQCN `\Modules\X\…`, string 'Modules\\X\\…'.
            preg_match_all('/Modules\\\\{1,2}(\w+)\\\\{1,2}([\w\\\\]+)/', file_get_contents($file->getPathname()), $m, PREG_SET_ORDER);

            foreach ($m as [, $target, $rest]) {
                $refs[] = [$owner, $relative, $target, str_replace('\\\\', '\\', $rest)];
            }
        }

        return $refs;
    }

    public static function modules(): array
    {
        return array_combine($dirs = array_map('basename', glob(self::MODULES.'/*', GLOB_ONLYDIR)), array_map(fn ($d) => [$d], $dirs));
    }

    #[DataProvider('modules')]
    public function test_module_only_uses_contracts_of_other_modules(string $module): void
    {
        $violations = [];

        foreach (self::references() as [$owner, $file, $target, $rest]) {
            if ($owner === $module && $target !== $module && ! str_starts_with($rest, 'Contracts\\') && $rest !== 'Contracts') {
                $violations[] = "{$file} → Modules\\{$target}\\{$rest}";
            }
        }

        $this->assertSame([], $violations, "Modul {$module} memakai internal modul lain");
    }

    public function test_scanner_detects_references(): void
    {
        $assessment = array_filter(self::references(), fn ($r) => $r[0] === 'Assessment' && $r[2] === 'Catalog');

        $this->assertNotEmpty($assessment, 'Assessment memakai Catalog\\Contracts (sanity check scanner)');
    }
}
