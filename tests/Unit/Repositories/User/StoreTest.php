<?php

use App\Models\User;
use App\Features\Auth\Data\UserRegisterData;
use App\Support\Data\DeviceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Helpers expected to be available globally per user instruction:
// function userRepo(): UserRepositoryEloquent { return app(UserRepositoryEloquent::class); }
// function makeUserRegisterData(string $email = '', string $password = '', bool $acceptTerms = true, bool $acceptMarketing = true, ?string $plan = 'free', ?DeviceData $device = null): UserRegisterData { /* ... */ }
// function makeDeviceData(string $id, string $platform, ?string $ip, ?string $model): DeviceData { /* ... */ }

// -------------------------------------------------
// store()
// -------------------------------------------------
test('store creates user with hashed password, free plan, and owner customer', function () {
    $data = makeUserRegisterData(
        email: 'john@example.test',
        password: 'secret-pass',
        acceptTerms: true,
        acceptMarketing: true,
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->not->toBeNull()
        ->and($user->email)->toBe('john@example.test')
        ->and(Hash::check('secret-pass', $user->password))->toBeTrue()
        ->and($user->accept_terms)->toBeTrue()
        ->and($user->plan)->toBe('free')
        ->and($user->customers()->count())->toBe(1)
        ->and($user->customers()->first()->is_owner)->toBeTrue()
        ->and($user->customers()->first()->accept_marketing)->toBeTrue();
});


test('store respects acceptMarketing=false on created owner customer', function () {
    $data = makeUserRegisterData(
        email: 'jane@example.test',
        password: 'strong',
        acceptTerms: true,
        acceptMarketing: false,
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    expect($user->customers()->first()->accept_marketing)->toBeFalse();
});


test('store ignores provided plan and forces free', function () {
    $data = makeUserRegisterData(
        email: 'forceplan@example.test',
        password: 'pw',
        acceptTerms: true,
        acceptMarketing: true,
        plan: 'pro', // the repo currently hardcodes "free"
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    expect($user->plan)->toBe('free');
});


test('store does not create a device even if device data is provided', function () {


    $data = makeUserRegisterData(
        email: 'withdevice@example.test',
        password: 'pw',
        acceptTerms: true,
        acceptMarketing: true,
        plan: 'free',
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    expect($user->devices()->count())->toBe(0);
});


test('store persists user and can be fetched back from DB with relations', function () {
    $data = makeUserRegisterData(
        email: 'persist@example.test',
        password: 'pw',
        acceptTerms: true,
        acceptMarketing: true,
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    $fresh = User::with('customers')->find($user->id);

    expect($fresh)->not->toBeNull()
        ->and($fresh->customers)->toHaveCount(1)
        ->and($fresh->customers->first()->is_owner)->toBeTrue();
});


test('store keeps exact email casing and plus-tags; password is hashed', function () {
    $data = makeUserRegisterData(
        email: 'MiXeD+tag@Example.TEST',
        password: 'case-pass',
        acceptTerms: true,
        acceptMarketing: true,
        device: makeDeviceData(
            id: 'dev-1',
            platform: 'ios',
            ip: '127.0.0.1',
            model: 'iPhone'
        )
    );

    $user = userRepo()->store($data);

    expect($user->email)->toBe('MiXeD+tag@Example.TEST')
        ->and(Hash::check('case-pass', $user->password))->toBeTrue();
});

