<?php

namespace Modules\Identity\Contracts;

use Modules\Identity\Contracts\Data\CustomerData;

/** Satu-satunya pintu baca data customer untuk modul lain. */
interface CustomerDirectory
{
    public function find(string $customerId): ?CustomerData;

    /** Customer yang sedang login di guard `customer` (null jika tamu). */
    public function current(): ?CustomerData;
}
