<?php

namespace Modules\Assessment\Contracts;

use Modules\Assessment\Contracts\Data\AssessmentResultData;

/** Hasil assessment yang sudah diklaim ke akun customer. */
interface AssessmentResults
{
    /** @return list<AssessmentResultData> terbaru dulu */
    public function forCustomer(string $customerId): array;
}
