<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Http\Resources\CategoryResource;

final class CategoryController
{
    public function __invoke(CatalogQuery $catalog): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalog->categories());
    }
}
