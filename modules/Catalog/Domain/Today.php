<?php

namespace Modules\Catalog\Domain;

use Illuminate\Support\Carbon;

/** Tanggal "hari ini" menurut zona waktu bisnis katalog (default Asia/Jakarta). */
final class Today
{
    public function __construct(private readonly string $timezone) {}

    public function date(): string
    {
        return Carbon::now($this->timezone)->toDateString();
    }
}
