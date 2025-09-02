<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\BMR;

interface BmrCalculatorInterface
{
    public function mifflinStJeor(float $weightKg, int $heightCm, int $ageYears, string $gender): float;

}
