<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class Faq implements Arrayable
{
    public function __construct(
        public string $id,
        public string $question,
        public string $answerHtml,
        public ?string $programSlug,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer_html' => $this->answerHtml,
            'program_slug' => $this->programSlug,
        ];
    }
}
