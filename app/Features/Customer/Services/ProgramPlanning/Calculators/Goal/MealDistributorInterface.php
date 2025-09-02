<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Goal;

interface MealDistributorInterface
{
    public function distribute(int $dailyKcal, array $mealUse, Goal $goal): array;

}
