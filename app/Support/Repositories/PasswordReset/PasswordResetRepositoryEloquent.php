<?php

namespace App\Support\Repositories\PasswordReset;

use App\Features\Auth\Data\ForgotPasswordData;
use App\Models\PasswordReset;
use App\Support\Repositories\BaseRepository;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetRepositoryEloquent extends BaseRepository implements PasswordResetRepository {

    protected string $model = PasswordReset::class;

    /**
     * @throws Throwable
     */
    public function storePasswordReset(ForgotPasswordData $forgotPasswordData): PasswordReset {
        $passwordReset = new PasswordReset();
        $passwordReset->email = $forgotPasswordData->email;
        $passwordReset->token = Str::random(200);
        $passwordReset->created_at = now();
        $passwordReset->saveOrFail();
        return $passwordReset;
    }

    public function findByEmailAndToken(string $email, string $token): ?PasswordReset {
        return $this->query()
            ->where('email', $email)
            ->where('token', $token)
            ->first();
    }

    public function remove(PasswordReset $passwordReset): void
    {
        $this->query()
            ->where('email', $passwordReset->email)
            ->where('token', $passwordReset->token)
            ->delete();
    }
}
