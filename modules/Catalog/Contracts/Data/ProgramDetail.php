<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class ProgramDetail implements Arrayable
{
    /**
     * @param  list<string>  $benefits
     * @param  list<string>  $requirements
     * @param  list<string>  $audience
     * @param  list<BatchSummary>  $batches  batch mendatang, urut start_date
     * @param  list<Faq>  $faqs
     */
    public function __construct(
        public ProgramSummary $summary,
        public string $descriptionHtml,
        public array $benefits,
        public array $requirements,
        public array $audience,
        public array $batches,
        public array $faqs,
    ) {}

    public function toArray(): array
    {
        return $this->summary->toArray() + [
            'description_html' => $this->descriptionHtml,
            'benefits' => $this->benefits,
            'requirements' => $this->requirements,
            'audience' => $this->audience,
            'batches' => array_map(fn (BatchSummary $b) => $b->toArray(), $this->batches),
            'faqs' => array_map(fn (Faq $f) => $f->toArray(), $this->faqs),
        ];
    }
}
