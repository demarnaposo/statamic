<?php

namespace Modules\Catalog\Contracts\Data;

use Illuminate\Contracts\Support\Arrayable;

/** Batch tunggal beserta kursi terkini dan program induknya (GET /batches/{id}). */
final readonly class BatchDetail implements Arrayable
{
    public function __construct(
        public BatchSummary $batch,
        public ProgramSummary $program,
    ) {}

    public function toArray(): array
    {
        return $this->batch->toArray() + [
            'seats_taken' => $this->batch->seatsTaken,
            'seats_left' => $this->batch->seatsLeft(),
            'program' => $this->program->toArray(),
        ];
    }
}
