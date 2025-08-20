<?php

namespace App\Features\Auth\Data;

use App\Support\Data\DeviceData;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class UserLoginData extends Data
{
    #[Required, Email, Max(255), Exists('users', 'email')]
    public string $email;

    #[Required]
    public string $password;

    #[Required]
    public DeviceData $device;

    #[Nullable]
    public ?string $customerId;
    public static function messages(): array
    {
        return [
            'email.exists' => "L'email ou le mot de passe est incorrect.",
        ];
    }
}
