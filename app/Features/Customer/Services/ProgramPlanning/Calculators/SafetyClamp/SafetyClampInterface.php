<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\SafetyClamp;

interface SafetyClampInterface
{
    public function clampDailyKcal(int $candidateDailyKcal, float $tdee, ?string $gender): int;

}
