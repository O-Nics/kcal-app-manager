<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Surplus;

use App\Features\ProgramPlanner\Domain\AdjustResult;

interface SurplusCalculatorInterface
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
