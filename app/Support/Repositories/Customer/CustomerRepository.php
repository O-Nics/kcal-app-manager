<?php

namespace App\Support\Repositories\Customer;

use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\PasswordCustomerData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Features\Customer\Data\WeightCustomerData;
use App\Models\Customer;
use App\Models\User;

interface CustomerRepository
{
    public function findById(string $id): ? Customer;

    public function createProgramCustomer(CreateProgramData $createProgramData, ?Customer $customer, User $user) : Customer;

    public function storeCustomer(User $user, RegisterCustomerData $registerCustomerData): Customer;

    public function updateNotification(Customer $customer, String $column, bool $value): void;

    public function deleteCustomer(Customer $customer): void;

    public function handlePasswordCustomer(Customer $customer, PasswordCustomerData $passwordCustomer): void;

    public function addWeight(Customer $customer, WeightCustomerData $weightCustomerData);


}
