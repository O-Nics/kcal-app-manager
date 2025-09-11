<?php

namespace App\Features\Customer\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class UpdateCustomerData extends Data
{

    #[Required]
    public string $requestAction;

    // Update Weight Unit
    #[Nullable]
    public ?string $weightUnit;

    // Update Water Volume
    #[Nullable]
    public ?int $volumeWater;

    // Update Username
    #[Nullable]
    public ?string $username;

    // Update Start Weight
    #[Nullable]
    public ?float $startWeight;

    // Update Target Weight
    #[Nullable]
    public ?float $targetWeight;

    // Update Steps
    #[Nullable]
    public ?int $steps;

    // Update Macros
    #[Nullable]
    public ?int $protein;

    #[Nullable]
    public ?int $carbs;

    #[Nullable]
    public ?int $fat;


    // Update Kcals
    #[Nullable]
    public ?int $kcal;

    // Update Height Unit
    #[Nullable]
    public ?string $heightUnit;

    // Update Height Unit
    #[Nullable]
    public ?float $hebdoChange;

    // Update Height
    #[Nullable]
    public ?float $height;

    // Update Goal
    #[Nullable, Exists('goals', 'id')]
    public ?int $goalId;

    // Update Gender
    #[Nullable]
    public ?string $gender;

    // Update Birth Date
    #[Nullable]
    #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y')]
    public ?Carbon $birthDate;


    // Update Activity
    #[Nullable, Exists('activity_levels', 'id')]
    public ?int $activityId;


    #[Nullable]
    public string $notificationColumn;

    #[Nullable]
    public string $notificationValue;

}
