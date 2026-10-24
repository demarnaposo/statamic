<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Category implements Arrayable
{
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $description,
    ) {}

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
