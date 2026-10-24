<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\BatchDetail;

/** @property BatchDetail $resource */
final class BatchDetailResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see BatchDetail::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
