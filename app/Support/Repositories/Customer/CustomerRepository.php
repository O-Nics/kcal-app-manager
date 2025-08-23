<?php

namespace App\Support\Repositories\Customer;

use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Models\Customer;
use App\Models\User;

interface CustomerRepository
{
    public function findById(string $id): ? Customer;

    public function createProgramCustomer(CreateProgramData $createProgramData, ?Customer $customer, User $user) : Customer;

    public function storeCustomer(User $user, RegisterCustomerData $registerCustomerData): Customer;


}
