<?php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Enums;

enum ActivityLevel: string
{
    case BEDREST       = '1';
    case SEDENTARY     = '2';
    case LIGHT_WALK    = '3';
    case LIGHT_SPORT   = '4';
    case MODERATE      = '5';
    case MODERATE_SPORT= '6';
    case ACTIVE        = '7';
    case VERY_ACTIVE   = '8';

    public static function fromId(string|int $id): self
    {
        return self::from((string)$id);
    }
}
