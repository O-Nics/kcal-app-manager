<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Deficit;

use App\Features\ProgramPlanner\Domain\AdjustResult;

interface DeficitCalculatorInterface
{
    public function compute(
        float $startWeightKg,
        ?float $requestedWeeklyKg,
        float $tdee,
        ?float $bmi,
        int $activityLevelId,
        bool $isRecomp = false
    ): AdjustResult;
}
