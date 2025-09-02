<?php

namespace App\Features\Customer\Services\ProgramPlanning\Calculators\CalorieCycler;

interface CalorieCyclerInterface
{
    public function cycle(int $dailyKcalTarget, float $tdee, ?string $gender, int $trainDays, int $restDays): array;

}
