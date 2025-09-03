<?php

namespace App\Support\Providers;

use App\Features\ProgramPlanner\Nutrition\Engine\ConfigPolicyProvider;
use App\Features\ProgramPlanner\Nutrition\Engine\ProgramEngine;
use App\Features\ProgramPlanner\Nutrition\Policy\DefaultProgramEngine;
use App\Features\ProgramPlanner\Nutrition\Policy\PolicyProvider;
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
