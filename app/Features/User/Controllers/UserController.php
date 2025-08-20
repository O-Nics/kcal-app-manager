<?php

namespace App\Features\User\Controllers;

use App\Features\User\Data\UserShowData;
use App\Support\Abstract\Controller;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\User\UserRepository;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{

    private UserRepository $userRepository;
    private CustomerRepository $customerRepository;
    public function __construct(
        UserRepository $userRepository,
        CustomerRepository $customerRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->customerRepository = $customerRepository;
    }


    public function user(UserShowData $userShowData): JsonResponse
    {
        $user = auth()->user();

        $customer = null;

        if($userShowData->customerId) {
            $customer = $this->customerRepository->findById($userShowData->customerId);
        }

        return response()->json([
            'success' => true,
            'user' => $user ? $user : null,
            'customer' => $customer ? $customer : null,
        ]);
    }
}
