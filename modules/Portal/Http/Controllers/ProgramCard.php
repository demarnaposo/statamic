<?php

namespace Modules\Portal\Http\Controllers;

use Modules\Catalog\Contracts\Data\ProgramSummary;

/** Props kartu program (subset ProgramSummary + URL halaman program di landing Astro). */
final class ProgramCard
{
    public static function from(ProgramSummary $p): array
    {
        return [
            'slug' => $p->slug,
            'title' => $p->title,
            'summary' => $p->summary,
            'issuer' => $p->issuer,
            'level' => $p->level,
            'duration' => $p->duration,
            'price' => $p->price,
            'effective_price' => $p->effectivePrice,
            'promo_active' => $p->promoActive,
            'next_batch_date' => $p->nextBatch?->startDate,
            'url' => config('portal.landing_url').'/program/'.rawurlencode($p->slug),
        ];
    }
}
