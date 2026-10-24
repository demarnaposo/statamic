<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Http\Resources\SiteResource;

final class SiteController
{
    public function __invoke(CatalogQuery $catalog): SiteResource|JsonResponse
    {
        $site = $catalog->site();

        return $site
            ? new SiteResource($site)
            : response()->json(['message' => 'Pengaturan situs belum diisi.'], 404);
    }
}
