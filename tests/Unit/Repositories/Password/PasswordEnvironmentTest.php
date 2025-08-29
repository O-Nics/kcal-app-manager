<?php

use App\Features\Auth\Data\ForgotPasswordData;
use App\Models\PasswordReset;
use App\Support\Repositories\PasswordReset\PasswordResetRepository;
use App\Support\Repositories\PasswordReset\PasswordResetRepositoryEloquent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function passwordResetRepo(): PasswordResetRepository {
    return app(PasswordResetRepositoryEloquent::class);
}

function makeForgotDto(string $email = 'user@example.com'): ForgotPasswordData {
    return ForgotPasswordData::from([
        'email' => $email,
    ]);
}
