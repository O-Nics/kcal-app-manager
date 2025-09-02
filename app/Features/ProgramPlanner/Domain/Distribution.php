<?php

namespace App\Features\ProgramPlanner\Domain;

use App\Features\Customer\Services\ProgramPlanning;

final readonly class Distribution
{
    /** @param array<string,int> $kcalBySlot */
    public function __construct(public array $kcalBySlot) {}
}
