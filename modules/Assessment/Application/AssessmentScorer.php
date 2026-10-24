<?php

namespace Modules\Assessment\Application;

use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Contracts\Data\Category;
use Modules\Catalog\Contracts\Data\ProgramSummary;

/**
 * Scoring server-side: tiap jawaban +1 untuk kategori opsi yang dipilih.
 * Skor seri dipecah berdasarkan urutan kategori di CatalogQuery::categories().
 */
final class AssessmentScorer
{
    public const MAX_RECOMMENDATIONS = 3;

    public function __construct(private readonly CatalogQuery $catalog) {}

    /**
     * @param  array<string, string>  $answers  question_id => option_id (sudah divalidasi)
     * @return array{version: int, scores: list<array{category: Category, score: int}>, recommended_category: Category, recommendations: list<ProgramSummary>}|null
     */
    public function score(AssessmentDefinition $definition, array $answers): ?array
    {
        $categories = $this->catalog->categories();

        if ($categories === []) {
            return null;
        }

        $points = array_fill_keys(array_map(fn (Category $c) => $c->slug, $categories), 0);

        foreach ($definition->questions as $question) {
            foreach ($question['options'] as $option) {
                if (($answers[$question['id']] ?? null) === $option['id']
                    && $option['category'] !== null
                    && array_key_exists($option['category'], $points)) {
                    $points[$option['category']]++;
                }
            }
        }

        $scores = [];
        foreach ($categories as $index => $category) {
            $scores[] = ['category' => $category, 'score' => $points[$category->slug], 'index' => $index];
        }

        usort($scores, fn ($a, $b) => [$b['score'], $a['index']] <=> [$a['score'], $b['index']]);
        $scores = array_map(fn ($s) => ['category' => $s['category'], 'score' => $s['score']], $scores);

        $recommended = $scores[0]['category'];

        return [
            'version' => $definition->version,
            'scores' => $scores,
            'recommended_category' => $recommended,
            // CatalogQuery::programs() sudah mengurutkan featured dulu.
            'recommendations' => array_slice(
                $this->catalog->programs(['category' => $recommended->slug]),
                0,
                self::MAX_RECOMMENDATIONS,
            ),
        ];
    }
}
