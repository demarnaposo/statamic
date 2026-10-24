<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\Category;

/** @property Category $resource */
final class CategoryResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see Category::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
