<?php

namespace App\Features\ProgramPlanner\Nutrition\Engine;

use App\Features\ProgramPlanner\Nutrition\Domain\Distribution;
use App\Features\ProgramPlanner\Nutrition\Domain\ProgramRequest;

interface ProgramEngine
{
    public function computeBmr(ProgramRequest $r): float;
    public function computeTdee(float $bmr, int $activityLevelId): float;

    public function computeDeficitPerDay(ProgramRequest $r, float $tdee): array; // [kcal(int), meta(array)]
    public function computeSurplusPerDay(ProgramRequest $r, float $tdee): array; // [kcal(int), meta(array)]
    public function clampDailyKcal(float $tdee, int $candidate, ?string $sex): int;

    public function planMacros(int $dailyKcal, ProgramRequest $r): array; // ['macros'=>Macros,'ratios'=>MacroRatios,'meta'=>array]
    public function distributeMeals(int $dailyKcal, array $mealUse, array $profile): Distribution;
    public function hydrationMl(ProgramRequest $r): int;
    public function steps(ProgramRequest $r, bool $cutFloor = false): int;

    public function estimateDurationDays(?float $targetWeight, float $startWeight, ?float $weeklyKg): ?int;
}
