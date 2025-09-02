<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\TDEE;

interface TdeeCalculatorInterface
{
    public function tdee(float $bmr, float $pal): float;

}
