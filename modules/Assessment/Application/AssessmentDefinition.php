<?php

namespace Modules\Assessment\Application;

/**
 * Definisi asesmen lengkap (termasuk pemetaan opsi → kategori). Hanya dipakai
 * di server; jangan pernah dikirim utuh ke browser.
 */
final readonly class AssessmentDefinition
{
    /**
     * @param  list<array{id: string, question: string, options: list<array{id: string, label: string, category: ?string}>}>  $questions
     */
    public function __construct(
        public int $version,
        public string $title,
        public string $intro,
        public array $questions,
    ) {}

    /** Bentuk publik sesuai kontrak `Assessment` (tanpa kategori/bobot). */
    public function toPublicArray(): array
    {
        return [
            'version' => $this->version,
            'title' => $this->title,
            'intro' => $this->intro,
            'questions' => array_map(fn (array $q) => [
                'id' => $q['id'],
                'question' => $q['question'],
                'options' => array_map(fn (array $o) => ['id' => $o['id'], 'label' => $o['label']], $q['options']),
            ], $this->questions),
        ];
    }
}
