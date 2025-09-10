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

class UpdateNutritionData extends Data
{

    #[Required]
    public int $protein;

    #[Required]
    public int $carbs;

    #[Required]
    public int $fat;

    public function __construct(int $protein, int $carbs, int $fat)
    {
        $this->protein = $protein;
        $this->carbs = $carbs;
        $this->fat = $fat;
    }


}
