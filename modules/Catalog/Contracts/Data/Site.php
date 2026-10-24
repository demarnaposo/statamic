<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Site implements Arrayable
{
    /**
     * @param  array{default_og_image_url: ?string, logo_url: ?string}  $seo
     * @param  array{title: string, subtitle: string, cta_label: string}  $hero
     * @param  list<array{label: string, value: string}>  $stats
     * @param  list<array{title: string, description: string}>  $steps
     * @param  array{email: string, phone: string, whatsapp: string, address: string}  $contact
     */
    public function __construct(
        public string $name,
        public array $seo,
        public string $tagline,
        public array $hero,
        public array $stats,
        public array $steps,
        public array $contact,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'seo' => $this->seo,
            'tagline' => $this->tagline,
            'hero' => $this->hero,
            'stats' => $this->stats,
            'steps' => $this->steps,
            'contact' => $this->contact,
        ];
    }
}
