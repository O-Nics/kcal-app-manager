<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Policies;

interface MealProfilePolicyInterface
{
    public function baseProfile(string $goal): array; // ['breakfast'=>0.2, ...]

}
