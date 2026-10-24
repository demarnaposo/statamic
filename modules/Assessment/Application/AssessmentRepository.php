<?php

namespace Modules\Assessment\Application;

use Modules\Catalog\Contracts\ContentCache;
use Statamic\Facades\GlobalSet;

/** Membaca global set `assessment` (di-cache lewat ContentCache milik Catalog). */
final class AssessmentRepository
{
    public function __construct(private readonly ContentCache $cache) {}

    public function get(): ?AssessmentDefinition
    {
        return $this->cache->remember('assessment', fn () => $this->load());
    }

    private function load(): ?AssessmentDefinition
    {
        $vars = GlobalSet::findByHandle('assessment')?->inDefaultSite();

        if (! $vars) {
            return null;
        }

        $questions = collect($vars->get('questions') ?? [])
            ->filter(fn ($q) => is_array($q) && filled($q['question_id'] ?? null) && ($q['enabled'] ?? true) !== false)
            ->map(fn (array $q) => [
                'id' => (string) $q['question_id'],
                'question' => (string) ($q['question'] ?? ''),
                'options' => collect($q['options'] ?? [])
                    ->filter(fn ($o) => is_array($o) && filled($o['option_id'] ?? null))
                    ->map(fn (array $o) => [
                        'id' => (string) $o['option_id'],
                        'label' => (string) ($o['label'] ?? ''),
                        'category' => self::ref($o['category'] ?? null),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $q) => $q['options'] !== [])
            ->values()
            ->all();

        // Global ada tetapi belum berisi pertanyaan = belum tersedia → 404.
        if ($questions === []) {
            return null;
        }

        return new AssessmentDefinition(
            version: (int) $vars->get('version', 1),
            title: (string) $vars->get('title', ''),
            intro: (string) $vars->get('intro', ''),
            questions: $questions,
        );
    }

    private static function ref(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return filled($value) ? (string) $value : null;
    }
}
