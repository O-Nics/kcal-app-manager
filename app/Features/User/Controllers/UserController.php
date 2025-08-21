<?php

namespace App\Features\User\Controllers;

use App\Features\User\Data\AddPasswordData;
use App\Features\User\Data\LoginToCustomerData;
use App\Features\User\Data\UpdateMailData;
use App\Features\User\Data\UpdatePasswordData;
use App\Features\User\Data\UserShowData;
use App\Features\User\Resources\DeviceResource;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Data\DeviceData;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\User\UserRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

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

    public function devices(): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $devices = $user->devices()->get();

        return response()->json([
            'success' => true,
            'devices' => DeviceResource::collection($devices),
        ]);
    }

    public function logout(DeviceData $deviceData): \Symfony\Component\HttpFoundation\JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->userRepository->deviceLogout($deviceData,$user);

        return response()->json([
            'success' => true,
            'message' => "Vous avez été déconnecté."
        ]);

    }

    public function loginToCustomer(LoginToCustomerData $loginToCustomerData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->userRepository->loginToCustomer($user, $loginToCustomerData);
        return response()->json([
            'success' => true,
            'message' => "Device connecté."
        ]);

    }

    public function updateEmail(UpdateMailData $updateMailData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if($this->userRepository->findByEmail($updateMailData->email)) {
            return response()->json([
                'success' => false,
                'message' => "L'email est déjà utilisé."
            ], 401);
        }
        $this->userRepository->changeEmail($user, $updateMailData);
        return response()->json([
            'success' => true,
            'message' => 'Email modifiée.'
        ]);

    }

    public function updatePassword(UpdatePasswordData $updatePasswordData) : JsonResponse {
        /** @var User $user */
        $user = auth()->user();

        if($this->userRepository->checkPassword($user, $updatePasswordData->oldPassword)) {
            $this->userRepository->updatePassword($user, $updatePasswordData);
        } else {
            return response()->json([
                'success' => false,
                'message' => "Le mot de passe est incorrect."
            ], 401);
        }
        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été modifié.'
        ]);
    }

    public function addPassword(AddPasswordData $addPasswordData) : JsonResponse {
        /** @var User $user */
        $user = auth()->user();

        $this->userRepository->updatePassword($user, $addPasswordData);

        return response()->json([
            'success' => true,
            'message' => 'Votre mot de passe a été modifié.'
        ]);
    }

    public function delete(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->userRepository->delete($user);
        return response()->json([
            'success' => true,
            'message' => "Votre compte a été supprimé."
        ]);
    }
}
