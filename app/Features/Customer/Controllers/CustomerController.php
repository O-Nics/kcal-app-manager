<?php

namespace App\Features\Customer\Controllers;

use App\Features\Auth\Data\RevokeTokenData;
use App\Features\Auth\Data\UserLoginData;
use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Enums\TokenAbility;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\Session\SessionRepository;
use App\Support\Repositories\User\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class CustomerController extends Controller
{
    private CustomerRepository $customerRepository;

    public function __construct(
        CustomerRepository $customerRepository,
    )
    {
        $this->customerRepository = $customerRepository;
    }

    public function createProgram(): JsonResponse
    {

    }

}
