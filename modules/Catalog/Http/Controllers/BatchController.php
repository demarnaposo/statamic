<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Application\StatamicCatalogQuery;
use Modules\Catalog\Http\Resources\BatchDetailResource;

/** Endpoint runtime: selalu membaca data segar (tanpa cache); header no-store dari middleware NoStore. */
final class BatchController
{
    public function __invoke(string $id, StatamicCatalogQuery $catalog): JsonResponse
    {
        $batch = $catalog->batch($id);

        return $batch
            ? (new BatchDetailResource($batch))->response()
            : response()->json(['message' => 'Batch tidak ditemukan.'], 404);
    }
}
