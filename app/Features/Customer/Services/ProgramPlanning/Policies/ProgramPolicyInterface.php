<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Policies;

interface ProgramPolicyInterface
{
    public function minTdeeRatio(): float;                 // ex: 0.70
    public function minMaleKcal(): int;                    // ex: 1700
    public function minFemaleKcal(): int;                  // ex: 1400
    public function minNeutralKcal(): int;                 // ex: 1500
    public function deficitHardCeiling(): float;           // ex: 0.35 (35%)

    public function deficitCapPct(float $bmi, int $activityLevelId, ?float $weeklyObservedPct = null, bool $isRecomp = false): float;
    public function surplusCapPct(float $bmi, int $activityLevelId, bool $isRecomp = false): float;
}
