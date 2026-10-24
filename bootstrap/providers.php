<?php

use App\Providers\AppServiceProvider;
use Modules\Assessment\AssessmentServiceProvider;
use Modules\Catalog\CatalogServiceProvider;
use Modules\Identity\IdentityServiceProvider;
use Modules\Portal\PortalServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    AssessmentServiceProvider::class,
    IdentityServiceProvider::class,
    PortalServiceProvider::class,
];
