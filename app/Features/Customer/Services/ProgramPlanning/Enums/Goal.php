<?php
// app/Domain/Nutrition/Enums/Goal.php
declare(strict_types=1);

namespace App\Features\Customer\Services\ProgramPlanning\Enums;

enum Goal: string
{
    case WEIGHT_LOSS   = 'weight_loss';
    case WEIGHT_GAIN   = 'weight_gain';
    case MAINTAIN      = 'maintain';
    case GAIN_MUSCLES  = 'gain_muscles';
    case RECOMP        = 'recomp';

    public static function fromId(string|int $id): self
    {
        return match ((string)$id) {
            '1' => self::WEIGHT_LOSS,
            '2' => self::WEIGHT_GAIN,
            '3' => self::MAINTAIN,
            '4' => self::GAIN_MUSCLES,
            '5' => self::RECOMP,
            default => self::MAINTAIN,
        };
    }
}
