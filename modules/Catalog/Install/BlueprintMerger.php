<?php

namespace Modules\Catalog\Install;

/**
 * Pembaruan blueprint yang aman (tanpa --force): hanya menambahkan field bawaan yang
 * belum ada DAN belum pernah dipasang (field yang dihapus admin tidak dipasang ulang).
 * Field, urutan, dan konfigurasi hasil edit admin di CP tidak disentuh, kecuali teks
 * bawaan lama yang tercantum di $stale (diganti hanya jika belum diubah admin).
 */
final class BlueprintMerger
{
    /**
     * @param  array  $current  isi blueprint yang tersimpan
     * @param  array  $defaults  isi blueprint bawaan modul
     * @param  array<string, array<string, array{0: string, 1: string}>>  $stale  handle => [config key => [teks lama, teks baru]]
     * @param  list<string>|null  $installed  handle bawaan yang pernah dipasang; null = belum pernah dicatat
     *                                        (semua field bawaan yang tidak ada dianggap dihapus admin)
     * @return array{0: array, 1: list<string>} [isi baru, daftar perubahan]
     */
    public static function merge(array $current, array $defaults, array $stale = [], ?array $installed = null): array
    {
        $existing = self::handles($current);
        $skip = array_merge($existing, $installed ?? self::handles($defaults));
        $changes = [];

        foreach ($defaults['tabs'] ?? [] as $tabHandle => $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                $missing = array_values(array_filter(
                    $section['fields'] ?? [],
                    fn ($f) => isset($f['handle']) && ! in_array($f['handle'], $skip, true),
                ));

                if ($missing === []) {
                    continue;
                }

                $current['tabs'][$tabHandle] ??= ['display' => $tab['display'] ?? $tabHandle, 'sections' => []];
                $sections = &$current['tabs'][$tabHandle]['sections'];
                $index = self::sectionIndex($sections ?? [], (string) ($section['display'] ?? ''));

                if ($index === null) {
                    $sections[] = $section + ['fields' => []];
                    $sections[array_key_last($sections)]['fields'] = $missing;
                } else {
                    array_push($sections[$index]['fields'], ...$missing);
                }
                unset($sections);

                foreach ($missing as $field) {
                    $skip[] = $field['handle'];
                    $changes[] = "+{$field['handle']}";
                }
            }
        }

        foreach ($current['tabs'] ?? [] as $t => $tab) {
            foreach ($tab['sections'] ?? [] as $s => $section) {
                foreach ($section['fields'] ?? [] as $f => $field) {
                    foreach ($stale[$field['handle'] ?? ''] ?? [] as $key => [$old, $new]) {
                        if (($field['field'][$key] ?? null) === $old) {
                            $current['tabs'][$t]['sections'][$s]['fields'][$f]['field'][$key] = $new;
                            $changes[] = "~{$field['handle']}.{$key}";
                        }
                    }
                }
            }
        }

        return [$current, $changes];
    }

    /** @return list<string> handle field tingkat atas di semua tab/section */
    public static function handles(array $contents): array
    {
        $handles = [];
        foreach ($contents['tabs'] ?? [] as $tab) {
            foreach ($tab['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    if (isset($field['handle'])) {
                        $handles[] = $field['handle'];
                    }
                }
            }
        }

        return $handles;
    }

    private static function sectionIndex(array $sections, string $display): ?int
    {
        foreach ($sections as $i => $section) {
            if ((string) ($section['display'] ?? '') === $display) {
                return $i;
            }
        }

        return null;
    }
}
