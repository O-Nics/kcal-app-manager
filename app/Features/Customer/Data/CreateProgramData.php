<?php

namespace App\Features\Customer\Data;

use App\Models\Customer;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class CreateProgramData extends Data
{

    #[Required]
    public string $username;

    #[Required]
    public float $height;

    #[Required]
    public float $startWeight;

    #[Required]
    public float $targetWeight;

    #[Required]
    public string $gender;

    #[Required, Exists('goals', 'id')]
    public string $goalId;
    #[Required, Exists('activity_levels', 'id')]
    public string $activityLevelId;

    #[Required]
    #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y')]
    public ?Carbon $birthDate = null;

    #[Required]
    public float $hebdoChange;

    #[Required]
    public string $heightUnit = 'cm';

    #[Required]
    public string $weightUnit = 'kg';

    #[Required]
    public array $mealUse;


    public ?array $trainingDaysOfWeek = [];



    public static function fromCustomer(Customer $customer, array $overrides = []): self
    {
        $base = [
            'username' => $customer->username ?? '',
            'height' => $customer->height ?? 0.0,
            'startWeight' => $customer->start_weight ?? 0.0,
            'targetWeight' => $customer->wanted_weight ?? 0.0,
            'gender' => $customer->gender ?? 'other',
            'goalId' => $customer->goal->id ?? 1,
            'activityLevelId' => $customer->activityLevel->id ?? 1,
            'birthDate' => $customer->birth_date
                ? (\is_string($customer->birth_date) ? Carbon::parse($customer->birth_date) : $customer->birth_date)
                : null,
            'hebdoChange' => $customer->obj_weight_hebdo_change ?? 0.0,
            'mealUse' => $customer->getMealTypeAsArray() ?? ['breakfast', 'lunch', 'dinner'],
            'trainingDaysOfWeek' => $customer->training_days_of_week ?? null,

        ];

        return self::from(array_merge($base, $overrides));
    }

}
