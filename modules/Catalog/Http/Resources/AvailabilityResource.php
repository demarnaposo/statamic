<?php

namespace Modules\Catalog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Modules\Catalog\Contracts\Data\BatchSummary;
use Modules\Catalog\Contracts\Data\ProgramDetail;

/** @property ProgramDetail $resource */
final class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->resource->summary;

        return [
            'program_slug' => $summary->slug,
            'price' => $summary->price,
            'promo_price' => $summary->promoPrice,
            'promo_until' => $summary->promoUntil,
            'effective_price' => $summary->effectivePrice,
            'promo_active' => $summary->promoActive,
            'batches' => array_map(fn (BatchSummary $b) => [
                'id' => $b->id,
                'code' => $b->code,
                'capacity' => $b->capacity,
                'seats_taken' => $b->seatsTaken,
                'seats_left' => $b->seatsLeft(),
                'status' => $b->status,
                'price' => $b->price,
            ], $this->resource->batches),
            'generated_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
