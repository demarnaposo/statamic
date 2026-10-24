<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Application\AttemptStore;
use Modules\Assessment\Application\PendingClaim;
use Modules\Identity\Contracts\Events\CustomerAuthenticated;
use Modules\Identity\Contracts\Events\CustomerRegistered;

/** Sinkron (bukan queue): token klaim ada di session request yang sedang berjalan. */
final class ClaimPendingAssessment
{
    public function __construct(
        private readonly PendingClaim $pending,
        private readonly AttemptStore $attempts,
    ) {}

    public function handle(CustomerRegistered|CustomerAuthenticated $event): void
    {
        if (($token = $this->pending->pull()) && $this->attempts->claim($token, $event->customerId)) {
            session()->flash('assessment_claimed', true);
        }
    }
}
