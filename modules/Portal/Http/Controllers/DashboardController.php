<?php

namespace Modules\Portal\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Modules\Assessment\Contracts\AssessmentResults;
use Modules\Catalog\Contracts\CatalogQuery;
use Modules\Catalog\Contracts\Data\ProgramSummary;
use Modules\Identity\Contracts\CustomerDirectory;

final class DashboardController
{
    public const MAX_RECOMMENDATIONS = 3;

    public function __invoke(CustomerDirectory $customers, AssessmentResults $results, CatalogQuery $catalog): Response
    {
        $customer = $customers->current();
        $latest = $results->forCustomer($customer->id)[0] ?? null;

        return Inertia::render('Dashboard', [
            'latestAssessment' => $latest?->toArray(),
            'recommendations' => $latest ? array_map(
                fn (ProgramSummary $p) => ProgramCard::from($p),
                array_slice($catalog->programs(['category' => $latest->recommendedCategorySlug]), 0, self::MAX_RECOMMENDATIONS),
            ) : [],
        ]);
    }
}
