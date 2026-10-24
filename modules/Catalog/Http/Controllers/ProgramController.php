<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Http\Resources\ProgramDetailResource;
use Modules\Catalog\Http\Resources\ProgramSummaryResource;

final class ProgramController
{
    public function index(Request $request, CatalogQuery $catalog): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'issuer' => ['nullable', 'in:internal,bnsp'],
            'featured' => ['nullable', 'in:0,1,true,false'],
        ], [
            'issuer.in' => 'Issuer harus internal atau bnsp.',
            'featured.in' => 'Featured harus 0 atau 1.',
            'category.*' => 'Kategori tidak valid.',
        ]);

        $featured = $validated['featured'] ?? null;

        return ProgramSummaryResource::collection($catalog->programs([
            'category' => $validated['category'] ?? null,
            'issuer' => $validated['issuer'] ?? null,
            'featured' => $featured === null ? null : in_array($featured, ['1', 'true'], true),
        ]));
    }

    public function show(string $slug, CatalogQuery $catalog): ProgramDetailResource|JsonResponse
    {
        $program = $catalog->program($slug);

        return $program
            ? new ProgramDetailResource($program)
            : response()->json(['message' => 'Program tidak ditemukan.'], 404);
    }
}
