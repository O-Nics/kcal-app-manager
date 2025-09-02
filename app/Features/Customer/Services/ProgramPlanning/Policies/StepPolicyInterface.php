<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Policies;

interface StepPolicyInterface
{
    public function stepsByActivity(int $activityLevelId): int;  // mapping
    public function cutFloorSteps(): int;                        // ex: 3000
}
