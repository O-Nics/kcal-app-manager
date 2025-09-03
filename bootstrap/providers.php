<?php

use App\Support\Providers\AppServiceProvider;
use App\Support\Providers\ProgramPlanningServiceProvider;
use App\Support\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    ProgramPlanningServiceProvider::class
];
