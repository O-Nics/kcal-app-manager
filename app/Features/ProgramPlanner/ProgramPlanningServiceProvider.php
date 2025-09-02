<?php

namespace App\Features\ProgramPlanner;

use App\Features\ProgramPlanner\Contracts\PolicyProvider;
use App\Features\ProgramPlanner\Contracts\ProgramEngine;
use App\Features\ProgramPlanner\Engine\ConfigPolicyProvider;
use App\Features\ProgramPlanner\Policy\DefaultProgramEngine;
use Illuminate\Support\ServiceProvider;

class ProgramPlanningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProgramEngine::class,
            DefaultProgramEngine::class
        );

        $this->app->bind(
            PolicyProvider::class,
            ConfigPolicyProvider::class
        );
    }

    public function boot(): void
    {
        // Optional: you could publish config, load migrations, etc.
    }
}
