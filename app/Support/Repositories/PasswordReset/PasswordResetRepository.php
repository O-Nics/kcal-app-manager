<?php

namespace App\Support\Repositories\PasswordReset;

use App\Features\Auth\Data\ForgotPasswordData;
use App\Models\PasswordReset;
use Throwable;

interface PasswordResetRepository
{

    public function storePasswordReset(ForgotPasswordData $forgotPasswordData): PasswordReset;

    public function findByEmailAndToken(string $email, string $token): ?PasswordReset;

    public function remove(PasswordReset $passwordReset): void;
}
