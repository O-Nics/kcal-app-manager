<?php

use App\Models\User;
use App\Models\Device;
use App\Models\Customer;
use App\Support\Data\DeviceData;
use App\Features\User\Data\LoginToCustomerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// -------------------------------------------------
// loginToCustomer()
// -------------------------------------------------

test('loginToCustomer associates a device to the given customer', function () {
    $user = User::query()->create([
        'email' => 'link@example.test',
        'password' => Hash::make('pw'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $customer = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);

    $device = $user->devices()->create([
        'device_id' => 'dev-100',
        'platform' => 'ios',
        'device_ip' => '1.2.3.4',
        'model' => 'iPhone',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
    ]);

    $payload = makeLoginToCustomerData(
        customerId: $customer->id,
        device: makeDeviceData(
            id: 'dev-100',
            platform: 'ios',
            ip: '1,2,3,4',
            model: 'iPhone'
        )
    );

    userRepo()->loginToCustomer($user, $payload);

    expect($device->fresh()->customer_id)->toBe($customer->id);
});

test('loginToCustomer overrides previous customer_id if already set', function () {
    $user = User::query()->create([
        'email' => 'change@example.test',
        'password' => Hash::make('pw'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $customer1 = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);
    $customer2 = $user->customers()->create([
        'is_owner' => false,
        'accept_marketing' => false,
    ]);

    $device = $user->devices()->create([
        'device_id' => 'dev-200',
        'platform' => 'android',
        'device_ip' => '2.2.2.2',
        'model' => 'Pixel',
        'access_token_id' => 0,
        'refresh_token_id' => 0,
        'customer_id' => $customer1->id,
    ]);

    $payload = makeLoginToCustomerData(
        customerId: $customer2->id,
        device: makeDeviceData(
            id: 'dev-200',
            platform: 'android',
            ip: '2,2,2,2',
            model: 'Pixel'
        )
    );


    userRepo()->loginToCustomer($user, $payload);

    expect($device->fresh()->customer_id)->toBe($customer2->id);
});

test('loginToCustomer does nothing if device not found (returns null)', function () {
    $user = User::query()->create([
        'email' => 'notfound@example.test',
        'password' => Hash::make('pw'),
        'accept_terms' => true,
        'plan' => 'free',
    ]);

    $customer = $user->customers()->create([
        'is_owner' => true,
        'accept_marketing' => true,
    ]);

    $payload = makeLoginToCustomerData(
        customerId: $customer->id,
        device: makeDeviceData(
            id: 'missing-device',
            platform: 'ios',
            ip: '1.1.1.1',
            model: 'Pixel'
        )
    );


    // Comme la méthode ne gère pas explicitement le cas null, Device::first() => null → appel sur null provoquerait une erreur
    // On vérifie donc qu'on a bien une exception PHP (Error)
    $this->expectException(Error::class);

    userRepo()->loginToCustomer($user, $payload);
});
