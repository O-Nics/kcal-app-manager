<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Enums;

enum MealSlot: string
{
    case BREAKFAST       = 'breakfast';
    case SNACK_MORNING   = 'snack_morning';
    case LUNCH           = 'lunch';
    case SNACK_AFTERNOON = 'snack_afternoon';
    case DINNER          = 'dinner';
}
