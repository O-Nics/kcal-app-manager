<?php

namespace App\Features\Customer\Controllers;

use App\Features\Auth\Data\RevokeTokenData;
use App\Features\Auth\Data\UserLoginData;
use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Features\Customer\Data\CreateProgramData;
use App\Models\Customer;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Ensure\EnsureCustomer;
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
    private EnsureCustomer $ensureCustomer;

    public function __construct(
        CustomerRepository $customerRepository,
        EnsureCustomer $ensureCustomer
    )
    {
        $this->customerRepository = $customerRepository;
        $this->ensureCustomer = $ensureCustomer;
    }

    public function customer(Customer $customer): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        return response()->json([
            'customer' => $customer,
        ]);
    }

    public function createProgram(Customer $customer, CreateProgramData $createProgramData): JsonResponse
    {
        /** @var User $user */

        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->createProgramCustomer($createProgramData, $customer, $user);


    }

}
