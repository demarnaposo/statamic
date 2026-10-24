<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Contracts\Data\Site;

/** @property Site $resource */
final class SiteResource extends JsonResource
{
    /** Bentuk field (whitelist) didefinisikan di DTO kontrak: {@see Site::toArray()}. */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
