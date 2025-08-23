<?php

namespace App\Support\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Mimes;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Illuminate\Http\UploadedFile;

class ImageData extends Data
{

    #[Required]
    #[Mimes('jpeg', 'png', 'jpg', 'gif', 'svg', 'webp', 'heic', 'heif', 'pdf')]
    #[Max(5120)]
    public UploadedFile $file;

}
