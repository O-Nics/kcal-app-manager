<?php

namespace App\Support\Repositories\User;


use App\Features\Auth\Data\UserRegisterData;
use App\Features\User\Data\AddPasswordData;
use App\Features\User\Data\LoginToCustomerData;
use App\Features\User\Data\UpdateMailData;
use App\Features\User\Data\UpdatePasswordData;
use App\Models\User;
use App\Support\Data\DeviceData;

interface UserRepository
{

    public function store(UserRegisterData $userRegisterData): User;

    public function deviceLogout(DeviceData $deviceData, User $user): void;

    public function loginToCustomer(User $user, LoginToCustomerData $loginToCustomerData): void;

    public function delete(User $user);

    public function checkPassword(User $user, string $password): bool;

    public function updatePassword(User $user, UpdatePasswordData|AddPasswordData $updatePasswordData): User;

    public function findByEmail(string $email) : ?User;

    public function changeEmail(User $user, UpdateMailData $updateMailData): User;

    public function changePassword(User $user, string $password): User;


}
