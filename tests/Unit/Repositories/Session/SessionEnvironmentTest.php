<?php

use App\Features\Auth\Services\TokenService;
use App\Features\Auth\Services\TokenPair;
use App\Models\User;
use App\Models\Device;
use App\Models\Session;
use App\Support\Data\DeviceData;
use App\Support\Repositories\Session\SessionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);



test('creates a new device and a session when none exists', function () {
    $user = User::factory()->create();
    $device = makeDeviceData();

    $tokens = app(TokenService::class)->issueFor($user, ['access'], ['refresh']);

    /** @var SessionRepository $repo */


    $session = sessionRepo()->createSession($user, $device, $tokens);

    expect($session)->toBeInstanceOf(Session::class)
        ->and($session->user_id)->toBe($user->id)
        ->and($session->device)->not->toBeNull();

    $createdDevice = $session->device;
    expect($createdDevice)->toBeInstanceOf(Device::class)
        ->and($createdDevice->user_id)->toBe($user->id)
        ->and($createdDevice->device_id)->toBe('dev-123')
        ->and($createdDevice->platform)->toBe('ios')
        ->and($createdDevice->device_ip)->toBe('1.2.3.4')
        ->and($createdDevice->model)->toBe('iPhone 14')
        ->and($createdDevice->access_token_id)->toBe($tokens->accessId())
        ->and($createdDevice->refresh_token_id)->toBe($tokens->refreshId());
});

test('updates an existing device and still creates a new session', function () {
    $user = User::factory()->create();

    $seedTokens = app(TokenService::class)->issueFor($user, ['seed-a'], ['seed-r']);

    // Device existant (même triplet user_id + device_id + platform)
    $existing = Device::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'dev-123',
        'platform' => 'ios',
        'device_ip' => '0.0.0.0',
        'access_token_id' => $seedTokens->accessId(),
        'refresh_token_id' => $seedTokens->refreshId(),
        'customer_id' => null,
        'model' => 'iPhone 13',
    ]);

    $device = makeDeviceData(id: 'dev-123', platform: 'ios', ip: '9.9.9.9', model: 'iPhone 14');

    $tokens = app(TokenService::class)->issueFor($user, ['a1'], ['r1']);

    $session = sessionRepo()->createSession($user, $device, $tokens);

    $existing->refresh();

    expect($session)->toBeInstanceOf(Session::class)
        ->and($session->device_id)->toBe($existing->id)
        ->and($existing->device_ip)->toBe('9.9.9.9')
        ->and($existing->access_token_id)->toEqual($tokens->accessId())
        ->and($existing->refresh_token_id)->toEqual($tokens->refreshId());
});

test('does not duplicate device across successive calls with same keys, but creates multiple sessions', function () {
    $user = User::factory()->create();
    $device = makeDeviceData('dev-dup', 'android', '8.8.8.8', 'Pixel 8');

    $svc = app(TokenService::class);

    $t1 = $svc->issueFor($user, ['a'], ['r']);
    $s1 = sessionRepo()->createSession($user, $device, $t1);

    $t2 = $svc->issueFor($user, ['a2'], ['r2']);
    $s2 = sessionRepo()->createSession($user, $device, $t2);

    // Un seul device pour ce triplet, mais deux sessions
    expect(Device::where('user_id', $user->id)->where('device_id', 'dev-dup')->where('platform', 'android')->count())
        ->toBe(1)
        ->and(Session::where('user_id', $user->id)->where('device_id', $s1->device_id)->count())
        ->toBe(2);

});

test('creates separate device rows when same device_id/platform is used by different users', function () {
    $u1 = User::factory()->create();
    $u2 = User::factory()->create();

    $device = makeDeviceData('shared-1', 'ios', '2.2.2.2', 'iPhone 15');

    $svc  = app(TokenService::class);

    sessionRepo()->createSession($u1, $device, $svc->issueFor($u1, ['a'], ['r']));
    sessionRepo()->createSession($u2, $device, $svc->issueFor($u2, ['a'], ['r']));

    // Deux devices (un par user), même device_id+platform mais user_id différent
    expect(Device::where('device_id','shared-1')->where('platform','ios')->count())->toBe(2);
});

test('updates ip and token ids on subsequent calls', function () {
    $user = User::factory()->create();

    $svc = app(TokenService::class);

    $s1 = sessionRepo()->createSession($user, makeDeviceData('dev-x','ios','1.1.1.1','iPhone'), $svc->issueFor($user, ['A1'], ['R1']));

    $s2 = sessionRepo()->createSession($user, makeDeviceData('dev-x','ios','5.5.5.5','iPhone'), $svc->issueFor($user, ['A2'], ['R2']));

    $device = $s2->device;

    expect($device->device_ip)->toBe('5.5.5.5')
        ->and($device->access_token_id)->toBeGreaterThan(0)
        ->and($device->refresh_token_id)->toBeGreaterThan(0)
        ->and($s2->id)->not->toBe($s1->id);
});


test('creates device even if model is null', function () {
    $user = User::factory()->create();
    $device = makeDeviceData('dev-null-model', 'ios', '7.7.7.7', null);

    $tokens = app(TokenService::class)->issueFor($user, ['a'], ['r']);

    $session = sessionRepo()->createSession($user, $device, $tokens);

    expect($session->device->model)->toBeNull();
});


test('creates a new session record each time even for same device', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    $s1 = sessionRepo()->createSession($user, makeDeviceData('d1','ios','1.1.1.1','iPhone'), $svc->issueFor($user, ['A'], ['R']));
    $s2 = sessionRepo()->createSession($user, makeDeviceData('d1','ios','1.1.1.1','iPhone'), $svc->issueFor($user, ['B'], ['R']));

    expect($s2->id)->not->toBe($s1->id)
        ->and(Session::where('device_id',$s1->device_id)->count())->toBe(2);
});

test('refreshes tokens on update of existing device', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    $firstTokens = $svc->issueFor($user, ['first'], ['rf1']);
    sessionRepo()->createSession($user, makeDeviceData('dev-refresh','ios','3.3.3.3','iPhone'), $firstTokens);

    $newTokens = $svc->issueFor($user, ['second'], ['rf2']);
    sessionRepo()->createSession($user, makeDeviceData('dev-refresh','ios','3.3.3.3','iPhone'), $newTokens);

    $device = Device::where('device_id','dev-refresh')->first();

    expect($device->access_token_id)->toEqual($newTokens->accessId())
        ->and($device->refresh_token_id)->toEqual($newTokens->refreshId());
});


test('allows one user to register multiple distinct devices', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    sessionRepo()->createSession($user, makeDeviceData('d1','ios','1.1.1.1','iPhone'), $svc->issueFor($user, ['A'], ['R']));
    sessionRepo()->createSession($user, makeDeviceData('d2','android','2.2.2.2','Pixel'), $svc->issueFor($user, ['B'], ['R']));

    expect(Device::where('user_id',$user->id)->count())->toBe(2);
});


test('links created session both to the user and the device', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    $session = sessionRepo()->createSession($user, makeDeviceData('link1','ios','9.9.9.9','iPhone'), $svc->issueFor($user, ['AA'], ['RR']));

    expect($session->user_id)->toBe($user->id)
        ->and($session->device->device_id)->toBe('link1');
});

test('updates device_ip if same device logs in from different IP', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    sessionRepo()->createSession($user, makeDeviceData('ip-change','ios','10.0.0.1','iPhone'), $svc->issueFor($user, ['X'], ['Y']));
    sessionRepo()->createSession($user, makeDeviceData('ip-change','ios','192.168.1.1','iPhone'), $svc->issueFor($user, ['Z'], ['W']));

    $device = Device::where('device_id','ip-change')->first();
    expect($device->device_ip)->toBe('192.168.1.1');
});


test('keeps devices and sessions after user deletion unless cascade is set', function () {
    $user = User::factory()->create();
    $svc  = app(TokenService::class);

    $session = sessionRepo()->createSession($user, makeDeviceData('keepme','ios','1.1.1.1','iPhone'), $svc->issueFor($user, ['A'], ['R']));

    $userId = $user->id;
    $user->delete();

    expect(Device::where('id',$session->device_id)->exists())->toBeTrue()
        ->and(Session::where('id',$session->id)->exists())->toBeTrue();
});
