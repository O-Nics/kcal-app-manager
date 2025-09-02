<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Duration;

interface DurationEstimatorInterface
{
    /**
     * @param float|null $startWeightKg
     * @param ?float $targetWeightKg
     * @param ?float $weeklyKgEffective
     * @param int $sign -1 pour perte, +1 pour gain
     * @return ?int nombre de jours
     */
    public function estimate(?float $startWeightKg, ?float $targetWeightKg, ?float $weeklyKgEffective, int $sign): ?int;
}
