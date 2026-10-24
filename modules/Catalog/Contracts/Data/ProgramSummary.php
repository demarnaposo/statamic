<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class ProgramSummary implements Arrayable
{
    /**
     * @param  'internal'|'bnsp'  $issuer
     */
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public string $sku,
        public ?Category $category,
        public string $issuer,
        public ?string $level,
        public ?string $duration,
        public string $summary,
        public ?string $imageUrl,
        public int $price,
        public ?int $promoPrice,
        public ?string $promoUntil,
        public int $effectivePrice,
        public bool $promoActive,
        public bool $featured,
        public ?BatchSummary $nextBatch,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'sku' => $this->sku,
            'category' => $this->category?->toArray(),
            'issuer' => $this->issuer,
            'level' => $this->level,
            'duration' => $this->duration,
            'summary' => $this->summary,
            'image_url' => $this->imageUrl,
            'price' => $this->price,
            'promo_price' => $this->promoPrice,
            'promo_until' => $this->promoUntil,
            'promo_active' => $this->promoActive,
            'effective_price' => $this->effectivePrice,
            'featured' => $this->featured,
            'next_batch' => $this->nextBatch?->toArray(),
        ];
    }
}
