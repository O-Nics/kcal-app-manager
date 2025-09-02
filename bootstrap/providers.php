<?php

use App\Features\ProgramPlanner\ProgramPlanningServiceProvider;
use App\Support\Providers\AppServiceProvider;
use App\Support\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    ProgramPlanningServiceProvider::class
];
