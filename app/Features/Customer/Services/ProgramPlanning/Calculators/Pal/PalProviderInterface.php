<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Pal;

interface PalProviderInterface
{
    public function palForActivity(int $activityLevelId): float; // ex: 1.2–1.85

}
