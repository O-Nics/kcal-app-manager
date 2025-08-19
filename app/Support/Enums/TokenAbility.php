<?php


namespace App\Support\Enums;

enum TokenAbility: string
{
    case REFRESH_TOKEN = 'refresh-token';
    case ACCESS_TOKEN = 'access-token';
}
