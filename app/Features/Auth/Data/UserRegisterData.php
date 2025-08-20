<?php

namespace App\Features\Auth\Data;

use App\Support\Data\DeviceData;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class UserRegisterData extends Data
{
    #[Required, Email, Max(255), Unique('users', 'email')]
    public string $email;

    #[Required, Min(8), Max(255), Confirmed]
    public string $password;

    #[Required]
    public DeviceData $device;

    #[Nullable]
    public ?string $userId;

    #[Required]
    public bool $acceptTerms;

    #[Required]
    public bool $acceptMarketing;

    public static function messages(): array
    {
        return [
            'email.required' => "L'email est obligatoire.",
            'email.unique' => "Cet email est déjà utilisé.",
            'email.max' => "L'email doit contenir au maximum 255 caractères.",
            'email.email' => "L'email doit être une adresse email valide.",
            'password.required' => "Le mot de passe est obligatoire.",
            'password.min' => "Le mot de passe doit contenir au moins 8 caractères.",
            'password.max' => "Le mot de passe doit contenir au maximum 255 caractères.",
            'password.confirmed' => "Les mots de passe ne correspondent pas.",
        ];
    }
}
