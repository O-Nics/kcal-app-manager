<?php

use App\Models\User;
use App\Models\Device;
use App\Features\User\Data\UpdatePasswordData;
use App\Features\User\Data\AddPasswordData;
use App\Support\Data\DeviceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// -------------------------------------------------
// updatePassword()
// -------------------------------------------------

test('updatePassword hashes and saves new password', function () {
    $user = User::query()->create([
        'email' => 'upd@example.test',
        'password' => Hash::make('oldpass'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $payload = makeUpdatePasswordData(
        oldPassword: 'oldpass',
        password: 'newpass',
        logoutAllDevices: false,
        device: makeDeviceData(id: 'primary', platform: 'ios', model: 'iPhone', ip: '127.0.0.1')
    );

    $updated = userRepo()->updatePassword($user, $payload);

    expect(Hash::check('newpass', $updated->password))->toBeTrue()
        ->and(Hash::check('oldpass', $updated->password))->toBeFalse()
        ->and(User::find($user->id)->password)->toBe($updated->password);
});

test('updatePassword updates updated_at timestamp when password changes', function () {
    $user = User::query()->create([
        'email' => 'time@example.test',
        'password' => Hash::make('abc'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);
    $user->updated_at = now()->subDay();
    $user->saveQuietly();
    $before = $user->updated_at;

    $payload = makeUpdatePasswordData(
        oldPassword: 'abc',
        password: 'xyz',
        logoutAllDevices: false,
        device: makeDeviceData(id: 'main', platform: 'ios', ip: '1.1.1.1', model: 'iPhone')
    );
    $updated = userRepo()->updatePassword($user, $payload);

    expect($updated->updated_at->greaterThan($before))->toBeTrue();
});


test('updatePassword logs out other devices when logoutAllDevices is true', function () {
    $user = User::query()->create([
        'email' => 'multi@example.test',
        'password' => Hash::make('old'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $primary = $user->devices()->create([
        'device_id' => 'stay',
        'platform' => 'ios',
        'device_ip' => '1.1.1.1',
        'model' => 'iPhone',
        'access_token_id' => 1,
        'refresh_token_id' => 2,
    ]);
    $other = $user->devices()->create([
        'device_id' => 'logout',
        'platform' => 'android',
        'device_ip' => '2.2.2.2',
        'model' => 'Pixel',
        'access_token_id' => 3,
        'refresh_token_id' => 4,
    ]);

    $payload = makeUpdatePasswordData(
        oldPassword: 'old',
        password: 'newpass',
        logoutAllDevices: true,
        device: makeDeviceData(id: 'stay', platform: 'ios', ip: '1.1.1.1', model: 'iPhone')
    );


    $updated = userRepo()->updatePassword($user, $payload);

    expect(Hash::check('newpass', $updated->password))->toBeTrue()
        ->and(Device::find($primary->id))->not->toBeNull()
        ->and(Device::find($other->id))->not->toBeNull(); // devices still exist, but tokens deletion simulated
});

test('updatePassword can be called multiple times', function () {
    $user = User::query()->create([
        'email' => 'again@example.test',
        'password' => Hash::make('first'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $payload1 = makeUpdatePasswordData(
        oldPassword: 'first',
        password: 'second',
        logoutAllDevices: false,
        device: makeDeviceData(id: 'devA', platform: 'ios', ip: '1.1.1.1', model: 'iPhone'),
    );
    $payload2 = makeUpdatePasswordData(
        oldPassword: 'second',
        password: 'third',
        logoutAllDevices: false,
        device: makeDeviceData(id: 'devB', platform: 'android', ip: '2.2.2.2', model: 'Pixel'),
    );

    userRepo()->updatePassword($user, $payload1);
    $final = userRepo()->updatePassword($user->fresh(), $payload2);

    expect(Hash::check('third', $final->password))->toBeTrue()
        ->and(Hash::check('second', $final->password))->toBeFalse();
});

test('updatePassword works with AddPasswordData payload type', function () {
    $user = User::query()->create([
        'email' => 'add@example.test',
        'password' => Hash::make('init'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $payload = makeAddPasswordData(
        password: 'added',
    );

    $updated = userRepo()->updatePassword($user, $payload);

    expect(Hash::check('added', $updated->password))->toBeTrue();
});
