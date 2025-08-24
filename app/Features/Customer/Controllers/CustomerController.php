<?php

namespace App\Features\Customer\Controllers;

use App\Features\Auth\Data\RevokeTokenData;
use App\Features\Auth\Data\UserLoginData;
use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\PasswordCustomerData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Features\Customer\Data\UpdateGoalData;
use App\Features\Customer\Data\UpdateNotificationData;
use App\Features\Customer\Data\WeightCustomerData;
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
use Illuminate\Support\Facades\Hash;
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

    public function customers(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json([
            'customer' => $user->customers,
        ]);
    }

    public function register(RegisterCustomerData $registerCustomerData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        try {

            $customer = $this->customerRepository->storeCustomer($user, $registerCustomerData);
            return response()->json([
                'success' => true,
                'customer' => new $customer
            ]);

        }catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }


    }

    public function createProgram(Customer $customer, CreateProgramData $createProgramData): JsonResponse
    {
        /** @var User $user */

        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->createProgramCustomer($createProgramData, $customer, $user);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }

    public function updateNotification(Customer $customer, UpdateNotificationData $updateNotificationData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        $this->customerRepository->updateNotification($customer, $updateNotificationData->notificationColumn, $updateNotificationData->notificationValue);
        return response()->json([
            'success' => true,
        ]);
    }

    public function delete(Customer $customer): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $this->customerRepository->deleteCustomer($customer);

        return response()->json([
            'success' => true,
            'message' => "Profil supprimé."
        ]);
    }

    public function password(Customer $customer, PasswordCustomerData $passwordCustomerData) : JsonResponse {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $this->customerRepository->handlePasswordCustomer($customer, $passwordCustomerData);

        return response()->json([
            'success' => true,
            'message' => "Mot de passe ajouté."
        ]);
    }

    public function checkPassword(Customer $customer, PasswordCustomerData $passwordCustomerData) : JsonResponse {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        if(!Hash::check($passwordCustomerData->passwordCustomer, $customer->profile_password)) {

            return response()->json([
                'success' => false,
                'message' => "Le code PIN est incorrect."
            ]);

        } else {

            return response()->json([
                'success' => true,
                'message' => "Le code PIN est correct."
            ]);
        }
    }

    public function  addWeight(Customer $customer, WeightCustomerData $weightCustomerData) : JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        try {
            $this->ensureCustomer->userCanManageCustomer($user, $customer);
            $weights = $this->customerRepository->addWeight($customer, $weightCustomerData);
            return response()->json([
                'success' => true,
                'weights' => WeightResource::collection($weights)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }


    }

    public function updateGoal(Customer $customer, UpdateGoalData $updateGoalData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);



    }


}
