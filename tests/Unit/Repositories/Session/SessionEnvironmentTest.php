<?php

use App\Features\Auth\Services\TokenService;
use App\Features\Auth\Services\TokenPair;
use App\Models\User;
use App\Models\Device;
use App\Models\Session;
use App\Support\Data\DeviceData;
use App\Support\Repositories\Session\SessionRepository;
use App\Support\Repositories\Session\SessionRepositoryEloquent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function sessionRepo(): SessionRepositoryEloquent {
    return app(SessionRepositoryEloquent::class);
}

function makeDeviceData(
    string $id ,
    string $platform ,
    ?string $ip ,
    ?string $model
): DeviceData {
    return DeviceData::from([
        'deviceId'        => $id,
        'devicePlatform'  => $platform,
        'deviceIp'        => $ip,
        'deviceModel'     => $model,
    ]);
}
