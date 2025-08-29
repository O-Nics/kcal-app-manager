<?php

use App\Models\User;
use App\Models\Device;
use App\Support\Data\DeviceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// -------------------------------------------------
// deviceLogout()
// -------------------------------------------------

test('deviceLogout deletes device ', function () {
    $user = User::query()->create([
        'email' => 'dev1@example.test',
        'password' => Hash::make('secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    // fake token IDs (could be sanctum tokens in real app)
    $device = $user->devices()->create([
        'device_id' => 'dev-123',
        'platform' => 'ios',
        'device_ip' => '127.0.0.1',
        'model' => 'iPhone',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
    ]);

    // on ajoute aussi d’autres devices
    $other = $user->devices()->create([
        'device_id' => 'dev-456',
        'platform' => 'android',
        'device_ip' => '127.0.0.2',
        'model' => 'Pixel',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
    ]);

    userRepo()->deviceLogout(
        makeDeviceData(
            id: 'dev-123',
            platform: 'ios',
            ip: '1.2.3.4',
            model: 'iPhone'
        ),
        $user
    );

    expect(Device::find($device->id))->toBeNull()
        ->and(Device::find($other->id))->not->toBeNull();
});

test('deviceLogout aborts with 401 if device does not exist', function () {
    $user = User::query()->create([
        'email' => 'nodev@example.test',
        'password' => Hash::make('x'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $this->expectException(HttpException::class);
    $this->expectExceptionMessage("Le device n'existe pas");

    userRepo()->deviceLogout(
        makeDeviceData(
            id: 'missing', platform: 'ios', ip: '127.0.0.1', model: 'iPhone'
        ),
        $user
    );
});

test('deviceLogout only deletes the targeted device, not others', function () {
    $user = User::query()->create([
        'email' => 'multi@example.test',
        'password' => Hash::make('secret'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $dev1 = $user->devices()->create([
        'device_id' => 'dev-A',
        'platform' => 'ios',
        'device_ip' => '1.1.1.1',
        'model' => 'iPhone',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
    ]);

    $dev2 = $user->devices()->create([
        'device_id' => 'dev-B',
        'platform' => 'android',
        'device_ip' => '1.1.1.2',
        'model' => 'Pixel',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
    ]);

    userRepo()->deviceLogout(
         makeDeviceData(
             id: 'dev-A',
             platform: 'ios',
             ip: '1,2,3,4',
             model: 'iPhone'
        ),
        $user
    );

    expect(Device::find($dev1->id))->toBeNull()
        ->and(Device::find($dev2->id))->not->toBeNull();
});
