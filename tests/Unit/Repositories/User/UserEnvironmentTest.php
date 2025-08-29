<?php

use App\Features\Auth\Data\UserRegisterData;
use App\Features\User\Data\AddPasswordData;
use App\Features\User\Data\LoginToCustomerData;
use App\Features\User\Data\UpdateMailData;
use App\Features\User\Data\UpdatePasswordData;
use App\Support\Data\DeviceData;
use App\Support\Repositories\User\UserRepositoryEloquent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function userRepo(): UserRepositoryEloquent {
    return app(UserRepositoryEloquent::class);
}

function makeUpdateEmailData(
    string $email = '',
): UpdateMailData {
    return UpdateMailData::from([
        'email'        => $email,
    ]);
}

function makeLoginToCustomerData(
    string $customerId,
    DeviceData $device,
): LoginToCustomerData {
    return LoginToCustomerData::from([
        'customerId' => $customerId,
        'device'     => $device,
    ]);
}

function makeUpdatePasswordData(
    string $oldPassword,
    string $password,
    bool $logoutAllDevices,
    DeviceData $device,
): UpdatePasswordData {
    return UpdatePasswordData::from([
        'oldPassword'      => $oldPassword,
        'password'         => $password,
        'logoutAllDevices' => $logoutAllDevices,
        'device'           => $device,
    ]);
}

function makeAddPasswordData(
    string $password,
): AddPasswordData {
    return AddPasswordData::from([
        'password' => $password,
    ]);
}

function makeUserRegisterData(
    string $email = '',
    string $password = '',
    bool $acceptTerms = true,
    bool $acceptMarketing = true,
    ?string $plan = 'free',
    ?DeviceData $device = null,
): UserRegisterData {
    return UserRegisterData::from([
        'email'            => $email,
        'password'         => $password,
        'acceptTerms'      => $acceptTerms,
        'acceptMarketing'  => $acceptMarketing,
        'plan'             => $plan,
        'device'           => $device,
    ]);
}


