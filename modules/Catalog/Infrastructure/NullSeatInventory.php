<?php

namespace Modules\Catalog\Infrastructure;

use Modules\Catalog\Contracts\SeatInventory;

final class NullSeatInventory implements SeatInventory
{
    public function seatsTaken(string $batchId): int
    {
        return 0;
    }
}
