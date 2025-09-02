<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Policies;

interface HydrationPolicyInterface
{
    public function litersPerKg(string $gender): float; // ex: 0.035 (H) / 0.031 (F)
    public function veryActiveBonusLiters(): float;     // ex: 0.5
    public function minMl(): int;                       // ex: 2000
    public function roundingStepMl(): int;              // ex: 250
}
