<?php

namespace App\Features\Customer\Data;

use App\Support\Data\DeviceData;
use App\Support\Data\ImageData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
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
use Spatie\LaravelData\DataCollection;

class WeightCustomerData extends Data
{

    #[Required]
    public int $weight;

    #[Nullable]
    #[DataCollectionOf(ImageData::class)]
    public DataCollection $files;

    #[Nullable]
    public ?float $waist;

    #[Nullable]
    public ?float $hip;

    #[Nullable]
    public ?float $chest;

    #[Nullable]
    public ?float $arm;

    #[Nullable]
    public ?float $thigh;

    #[Nullable]
    public ?array $documentIds;

}
