<?php

namespace App\Support\Repositories\Customer;

use App\Models\Customer;

interface CustomerRepository
{
    public function findById(string $id): ? Customer;

}
