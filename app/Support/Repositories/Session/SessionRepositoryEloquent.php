<?php

namespace App\Support\Repositories\Session;


use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenPair;
use App\Models\Device;
use App\Models\Session;
use App\Models\User;
use App\Support\Data\DeviceData;
use App\Support\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class SessionRepositoryEloquent extends BaseRepository implements SessionRepository
{

    /**
     * @throws Throwable
     */
    public function createSession(User $user, DeviceData $deviceData, TokenPair $tokenPair): Session
    {
        $device = Device::query()
            ->where('device_id', $deviceData->deviceId)
            ->where('platform', $deviceData->devicePlatform)
            ->where('user_id', $user->id)
            ->first();
        if(!$device) {
            $device = $user->devices()->create(
                [
                    'device_id' => $deviceData->deviceId,
                    'platform' => $deviceData->devicePlatform,
                    'device_ip' => $deviceData->deviceIp,
                    'access_token_id' => $tokenPair->accessId(),
                    'refresh_token_id' => $tokenPair->refreshId(),
                    'model' => $deviceData->deviceModel,
                ]
            );
        } else {
            $device->access_token_id = $tokenPair->accessId();
            $device->refresh_token_id = $tokenPair->refreshId();
            $device->device_ip = $deviceData->deviceIp;
            $device->saveOrFail();
        }

        $devices = Device::query()
            ->whereHas('sessions', function (Builder $query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        $session = new Session();
        $session->device()->associate($device);
        $session->user()->associate($user);
        $session->saveOrFail();
        return $session;
    }
}
