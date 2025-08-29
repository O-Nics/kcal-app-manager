<?php

use App\Features\Auth\Data\UserRegisterData;
use App\Features\User\Data\AddPasswordData;
use App\Features\User\Data\LoginToCustomerData;
use App\Features\User\Data\UpdateMailData;
use App\Features\User\Data\UpdatePasswordData;
use App\Support\Data\DeviceData;
use App\Support\Repositories\Customer\CustomerRepositoryEloquent;
use App\Support\Repositories\User\UserRepositoryEloquent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function customerRepo(): CustomerRepositoryEloquent {
    return app(CustomerRepositoryEloquent::class);
}


