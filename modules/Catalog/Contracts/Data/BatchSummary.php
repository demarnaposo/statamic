<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

final readonly class BatchSummary implements Arrayable
{
    /**
     * @param  'online'|'offline'|'hybrid'  $mode
     * @param  'open'|'closed'|'full'  $status
     */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public string $startDate,
        public string $endDate,
        public string $mode,
        public ?string $location,
        public int $capacity,
        public int $price,
        public string $status,
        public int $seatsTaken = 0,
    ) {}

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->seatsTaken);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'mode' => $this->mode,
            'location' => $this->location,
            'capacity' => $this->capacity,
            'price' => $this->price,
            'status' => $this->status,
        ];
    }
}
