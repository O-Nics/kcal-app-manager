<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\Step;

interface StepsCalculatorInterface
{
    public function steps(int $activityLevelId, bool $enCut = false): int;

}
