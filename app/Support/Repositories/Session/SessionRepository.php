<?php

namespace App\Support\Repositories\Session;


use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenPair;
use App\Models\Session;
use App\Models\User;
use App\Support\Data\DeviceData;
use Illuminate\Http\Request;
interface SessionRepository
{
    public function createSession(User $user, DeviceData $deviceData, TokenPair $tokenPair, Request $request): Session;
}
