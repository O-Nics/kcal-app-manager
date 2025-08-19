<?php

namespace App\Support\Data;

use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class DeviceData extends Data
{

    #[Required]
    public string $deviceId;

    #[Required]
    public string $devicePlatform;

    #[Nullable]
    public ?string $deviceModel;

    #[Nullable]
    public ?string $deviceIp;


}
