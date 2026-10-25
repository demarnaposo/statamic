<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Application\StatamicCatalogQuery;

/** Endpoint runtime: harga terkini semua program (satu request per halaman Astro); tanpa cache, header no-store dari NoStore. */
final class PriceController
{
    public function __invoke(Request $request, StatamicCatalogQuery $catalog): JsonResponse
    {
        $slugs = array_filter(explode(',', (string) $request->query('slugs', '')));

        $prices = [];
        foreach ($catalog->programs() as $p) {
            if ($slugs && ! in_array($p->slug, $slugs, true)) {
                continue;
            }
            $prices[$p->slug] = [
                'price' => $p->price,
                'promo_price' => $p->promoPrice,
                'promo_until' => $p->promoUntil,
                'promo_active' => $p->promoActive,
                'effective_price' => $p->effectivePrice,
            ];
        }

        // (object) agar daftar kosong tetap terserialisasi sebagai {} bukan [].
        return response()->json(['data' => (object) $prices]);
    }
}
