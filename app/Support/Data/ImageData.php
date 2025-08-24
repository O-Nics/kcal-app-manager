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
    public static function messages(): array
    {
        return [
            'file.required' => 'Le fichier est obligatoire.',
            'file.mimes' => 'Le fichier doit être de type jpeg, png, jpg, gif, svg, webp, heic, heif ou pdf.',
            'file.max' => 'Le fichier ne doit pas dépasser 2 Ko.',
        ];
    }

}
