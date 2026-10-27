<?php

use App\Providers\AppServiceProvider;
use Modules\Assessment\AssessmentServiceProvider;
use Modules\Catalog\CatalogServiceProvider;

return [
    AppServiceProvider::class,
    CatalogServiceProvider::class,
    AssessmentServiceProvider::class,
];
