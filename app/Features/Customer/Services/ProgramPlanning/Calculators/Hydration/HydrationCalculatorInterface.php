<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Hydration;

interface HydrationCalculatorInterface
{
    public function waterMl(float $weightRefKg, int $activityLevelId, ?string $gender): int;

}
