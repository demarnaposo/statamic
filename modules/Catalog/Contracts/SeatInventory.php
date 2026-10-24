<?php

namespace Modules\Catalog\Contracts;

/**
 * Jumlah kursi terisi per batch. Implementasi default (NullSeatInventory)
 * selalu 0; modul Commerce nanti mengikat implementasi sebenarnya.
 */
interface SeatInventory
{
    public function seatsTaken(string $batchId): int;
}
