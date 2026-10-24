<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Application\StatamicCatalogQuery;
use Modules\Catalog\Http\Resources\AvailabilityResource;

/** Endpoint runtime: selalu membaca data segar (tanpa cache); header no-store dari middleware NoStore. */
final class AvailabilityController
{
    public function __invoke(string $slug, StatamicCatalogQuery $catalog): JsonResponse
    {
        $program = $catalog->program($slug);

        return $program
            ? (new AvailabilityResource($program))->response()
            : response()->json(['message' => 'Program tidak ditemukan.'], 404);
    }
}
