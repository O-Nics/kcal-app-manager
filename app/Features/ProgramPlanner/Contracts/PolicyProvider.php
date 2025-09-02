<?php

namespace App\Features\ProgramPlanner\Contracts;

interface PolicyProvider
{
    public function palFor(int $activity): float;
    public function stepsFor(int $activity): int;
    public function mealProfileFor(string $goal): array;

    public function deficitCapPct(float $bmi, int $activity, bool $isRecomp): float;
    public function surplusCapPct(float $bmi, int $activity, bool $isRecomp): float;

    public function minTdeeRatio(): float;
    public function minDailyKcalAbs(?string $sex): int;

    public function proteinBoundsGPerKg(string $goal): array; // [min,default,max]
    public function fatPctRange(string $goal, int $activity): array; // [min,max]
    public function fatMinGPerKg(): float;
    public function fatMinAbsG(): int;
    public function carbsAbsMinG(): int;
    public function carbsMinPerKg(int $activity, ?array $trainDays, string $goal): float;
    public function enforceProteinOverCarbs(): bool;
}
