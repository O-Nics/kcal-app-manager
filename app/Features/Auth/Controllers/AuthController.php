<?php

namespace App\Features\Auth\Controllers;

use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Support\Abstract\Controller;
use App\Support\Enums\TokenAbility;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\User\UserRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class AuthController extends Controller
{
    private UserRepository $userRepository;
    private CustomerRepository $customerRepository;

    private TokenService $tokenService;

    public function __construct(
        UserRepository $userRepository,
        CustomerRepository $customerRepository,
        TokenService $tokenService
    )
    {
        $this->userRepository = $userRepository;
        $this->customerRepository = $customerRepository;
        $this->tokenService = $tokenService;
    }

    public function register(UserRegisterData $userRegisterData): JsonResponse
    {

        try {
            $user = $this->userRepository->store($userRegisterData);

            $tokens = $this->tokenService->issueFor(
                $user,
                now()->addMinutes(config('sanctum.ac_expiration')),
                now()->addDays(config('sanctum.rt_expiration')),
                [TokenAbility::ACCESS_TOKEN->value],
                [TokenAbility::REFRESH_TOKEN->value]
            );

            return response()->json([
                'success' => true,
                'message' => 'Registration successful.',
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
