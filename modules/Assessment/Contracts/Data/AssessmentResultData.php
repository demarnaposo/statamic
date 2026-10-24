<?php

namespace Modules\Assessment\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class AssessmentResultData implements Arrayable
{
    /**
     * @param  list<array{slug: string, title: string, score: int}>  $scores  urut skor tertinggi
     */
    public function __construct(
        public string $id,
        public int $version,
        public string $recommendedCategorySlug,
        public string $recommendedCategoryTitle,
        public array $scores,
        public string $takenAt,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'recommended_category' => ['slug' => $this->recommendedCategorySlug, 'title' => $this->recommendedCategoryTitle],
            'scores' => $this->scores,
            'taken_at' => $this->takenAt,
        ];
    }
}
