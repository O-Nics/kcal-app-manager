<?php

namespace App\Features\Customer\Data;

use App\Support\Data\DeviceData;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class CreateProgramData extends Data
{
    #[Required]
    public string $customerId;

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

    #[Required]
    public string $goalId;


    #[Required]
    public string $activityLevelId;

    #[Required]
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
    public ?Carbon $birthDate = null;

    #[Required]
    #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d')]
    public ?Carbon $deadlineDate = null;

    #[Required]
    public int $mealCountPerDay;
}
