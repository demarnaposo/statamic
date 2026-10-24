<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Http\Resources\TestimonialResource;

final class TestimonialController
{
    public function __invoke(CatalogQuery $catalog): AnonymousResourceCollection
    {
        return TestimonialResource::collection($catalog->testimonials());
    }
}
