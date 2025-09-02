<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Policies;

interface MacroPolicyInterface
{
    // Protéines
    public function proteinGPerKgDefault(string $goal): float;   // ex: 2.0 en loss, 1.8 en gain
    public function proteinGPerKgMin(string $goal): float;       // ex: 1.6–1.8
    public function proteinGPerKgMax(string $goal): float;       // ex: 2.2–2.4
    public function proteinPctHardCap(string $goal): int;        // ex: 30–32%

    // Lipides
    public function fatPctRange(string $goal, int $activityLevelId): array; // [minPct, maxPct]
    public function fatMinGPerKg(): float;                                   // ex: 0.8
    public function fatMinAbsG(): int;                                       // ex: 50

    // Glucides (planchers & cibles)
    public function absMinCarbG(): int;                 // ex: 100–130
    public function minCarbPerKg(int $activityLevelId, ?int $trainDays, string $goal): float; // dynamique
    public function targetCarbPerKg(?int $trainDays, string $goal): float;                     // hypertrophie etc.

    // Toggles
    public function enforceProteinOverCarbs(): bool;    // ex: false/true
}
