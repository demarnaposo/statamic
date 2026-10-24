<?php

namespace Modules\Catalog\Install;

/** Helper kecil untuk menyusun konfigurasi field blueprint Statamic. */
final class Fields
{
    public const UNIQUE_ENTRY = 'new \Statamic\Rules\UniqueEntryValue({collection}, {id}, {site})';

    public static function make(string $handle, string $type, string $display, array $config = []): array
    {
        return ['handle' => $handle, 'field' => array_merge(['type' => $type, 'display' => $display], $config)];
    }

    public static function section(string $display, array $fields, ?string $instructions = null): array
    {
        return array_filter([
            'display' => $display,
            'instructions' => $instructions,
            'fields' => $fields,
        ], fn ($v) => $v !== null);
    }

    public static function tab(string $display, array $sections): array
    {
        return ['display' => $display, 'sections' => $sections];
    }

    public static function entries(string $handle, string $display, string $collection, array $config = []): array
    {
        return self::make($handle, 'entries', $display, array_merge([
            'collections' => [$collection],
            'max_items' => 1,
            'mode' => 'select',
            'create' => false,
        ], $config));
    }

    public static function category(string $handle = 'category', string $display = 'Kategori', array $config = []): array
    {
        return self::make($handle, 'terms', $display, array_merge([
            'taxonomies' => ['categories'],
            'max_items' => 1,
            'mode' => 'select',
            'create' => false,
        ], $config));
    }
}
