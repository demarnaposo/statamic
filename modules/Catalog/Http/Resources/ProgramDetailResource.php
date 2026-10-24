<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\ProgramDetail;

/** @property ProgramDetail $resource */
final class ProgramDetailResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see ProgramDetail::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
