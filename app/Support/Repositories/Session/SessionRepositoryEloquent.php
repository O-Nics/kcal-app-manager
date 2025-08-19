<?php

namespace App\Support\Repositories\Session;


use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenPair;
use App\Models\Session;
use App\Models\User;
use App\Support\Data\DeviceData;
use App\Support\Repositories\BaseRepository;

class SessionRepositoryEloquent extends BaseRepository implements SessionRepository
{

    public function createSession(User $user, DeviceData $deviceData, TokenPair $tokenPair): Session
    {
        $session = new Session();
        $session->user()->associate(auth()->user());
        $session->device()->associate(request()->device);
        $session->action_type = Session::CREATE_SESSION_ACTION_TYPE_LOGIN_CREDENTIALS;
        $session->save();

        return $session;
    }
}
