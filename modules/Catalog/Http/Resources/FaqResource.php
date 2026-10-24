<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\Faq;

/** @property Faq $resource */
final class FaqResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see Faq::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
