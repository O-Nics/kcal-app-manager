<?php
// app/Domain/Nutrition/ValueObjects/ProgramRequest.php
declare(strict_types=1);

namespace App\Features\ProgramPlanner\Nutrition\Domain;

;



final class ProgramRequest
{
    public function __construct(
        public readonly string $goal,          // 'weight_loss' | 'weight_gain' | 'gain_muscles' | 'maintain' | 'recomp'
        public readonly int    $activityLevelId, // 1..8
        public readonly float  $weight,        // kg
        public readonly float  $height,        // cm
        public readonly int    $age,           // years
        public readonly ?string $sex,          // 'male'|'female'|null
        public readonly float  $bmi,           // precomputed ok
        public readonly float  $startWeight,   // kg
        public readonly ?float $targetWeight,  // kg or null
        public readonly ?float $weeklyKg,      // signed kg/week (loss negative, gain positive) or null
        public readonly array  $mealUse,       // ['breakfast'=>true, ...]
        public readonly array  $mealProfile,   // ['breakfast'=>0.25, ...] (déjà résolu via Policy)
        public readonly ?array   $trainDays = [],   // 0..7
        public readonly ?int   $hydrationMlPerKg = null, // override per-user if any
    ) {}

    public function isRecomp(): bool
    {
        return $this->goal === 'recomp';
    }

    public function isCut(): bool
    {
        return $this->goal === 'weight_loss' || $this->goal === 'recomp';
    }
}
