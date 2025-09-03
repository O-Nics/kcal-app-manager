<?php

namespace App\Features\ProgramPlanner\Nutrition\Domain;

final readonly class AdjustResult
{
    public function __construct(
        public int     $adjustKcalPerDay,          // +/- kcal/j
        public ?float  $weeklyKg,               // effectif
        public ?float  $weeklyPct,              // effectif
        public ?float  $capPct,                 // cap TDEE appliqué
        public ?string $cappedBy,              // 'tdee_cap' | 'weekly_pct_cap' | null
        public ?float  $maxWeeklyKgByTdee = null
    ) {}
}
