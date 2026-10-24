<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\Testimonial;

/** @property Testimonial $resource */
final class TestimonialResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see Testimonial::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
