<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogQuery;

/** Placeholder sampai modul Commerce ada: tautan checkout dari landing tidak 404. */
final class CheckoutController
{
    public const MAX_QTY = 50;

    public function __invoke(Request $request, CatalogQuery $catalog): Response
    {
        $batchId = $request->query('batch');
        $detail = is_string($batchId) && $batchId !== '' ? $catalog->batch($batchId) : null;
        $qty = max(1, min(self::MAX_QTY, (int) $request->query('qty', 1)));

        return Inertia::render('Checkout/ComingSoon', [
            'qty' => $qty,
            'batch' => $detail ? [
                'id' => $detail->batch->id,
                'code' => $detail->batch->code,
                'title' => $detail->batch->title,
                'start_date' => $detail->batch->startDate,
                'end_date' => $detail->batch->endDate,
                'mode' => $detail->batch->mode,
                'location' => $detail->batch->location,
                'status' => $detail->batch->status,
                'seats_left' => $detail->batch->seatsLeft(),
                'price' => $detail->batch->price,
                'total' => $detail->batch->price * $qty,
                'program' => ProgramCard::from($detail->program),
            ] : null,
        ]);
    }
}
