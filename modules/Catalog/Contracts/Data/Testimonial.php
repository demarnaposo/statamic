<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Testimonial implements Arrayable
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $role,
        public ?string $company,
        public string $quote,
        public ?string $programSlug,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'company' => $this->company,
            'quote' => $this->quote,
            'program_slug' => $this->programSlug,
        ];
    }
}
