<?php

namespace Modules\Portal\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Assessment\Contracts\AssessmentResults;
use Modules\Assessment\Contracts\Data\AssessmentResultData;
use Modules\Identity\Contracts\CustomerDirectory;

final class AssessmentHistoryController
{
    public function __invoke(CustomerDirectory $customers, AssessmentResults $results): Response
    {
        return Inertia::render('Assessments/Index', [
            'results' => array_map(fn (AssessmentResultData $r) => $r->toArray(), $results->forCustomer($customers->current()->id)),
            'assessmentUrl' => config('portal.landing_url').'/assessment',
        ]);
    }
}
