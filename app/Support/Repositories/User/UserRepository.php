<?php

namespace App\Support\Repositories\User;


use App\Features\Auth\Data\UserRegisterData;
use App\Models\User;

interface UserRepository
{

    public function store(UserRegisterData $userRegisterData): User;
}
