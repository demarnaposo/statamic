<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Http\Resources\FaqResource;

final class FaqController
{
    public function __invoke(CatalogQuery $catalog): AnonymousResourceCollection
    {
        return FaqResource::collection($catalog->generalFaqs());
    }
}
