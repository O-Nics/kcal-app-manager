<?php

namespace App\Features\Customer\Controllers;

use App\Feature\Customer\Resources\CustomerResource;
use App\Features\Auth\Data\RevokeTokenData;
use App\Features\Auth\Data\UserLoginData;
use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Features\Customer\Data\AddWaterData;
use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\DelWaterData;
use App\Features\Customer\Data\PasswordCustomerData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Features\Customer\Data\UpdateActivityData;
use App\Features\Customer\Data\UpdateBirthDateData;
use App\Features\Customer\Data\UpdateCustomerData;
use App\Features\Customer\Data\UpdateGenderData;
use App\Features\Customer\Data\UpdateGoalData;
use App\Features\Customer\Data\UpdateHebdoChangeData;
use App\Features\Customer\Data\UpdateHeightData;
use App\Features\Customer\Data\UpdateHeightUnitData;
use App\Features\Customer\Data\UpdateKcalData;
use App\Features\Customer\Data\UpdateNotificationData;
use App\Features\Customer\Data\UpdateNutritionData;
use App\Features\Customer\Data\UpdateStepsData;
use App\Features\Customer\Data\UpdateTargetOrStartWeightData;
use App\Features\Customer\Data\UpdateUsernameData;
use App\Features\Customer\Data\UpdateWaterVolumeData;
use App\Features\Customer\Data\UpdateWeightUnitData;
use App\Features\Customer\Data\WeightCustomerData;
use App\Features\Customer\Services\CustomerUpdater;
use App\Features\Customer\Services\OldProgramPlanner;
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
use function Symfony\Component\String\u;

class CustomerController extends Controller
{
    protected CustomerRepository $customerRepository;
    protected EnsureCustomer $ensureCustomer;

    protected CustomerUpdater $customerUpdater;

    public function __construct(
        CustomerRepository $customerRepository,
        EnsureCustomer $ensureCustomer,
        CustomerUpdater $customerUpdater
    )
    {
        $this->customerRepository = $customerRepository;
        $this->ensureCustomer = $ensureCustomer;
        $this->customerUpdater = $customerUpdater;
    }

    public function customer(Customer $customer): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        return response()->json([
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function customers(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return response()->json([
            'customer' => CustomerResource::collection($user->customers),
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
                'customer' => new CustomerResource($customer)
            ]);

        }catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }


    }

    public function updateCustomer(Customer $customer, UpdateCustomerData $customerData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        switch ($customerData->requestAction){

            case 'updateWeightUnit':
                $customer = $this->customerUpdater->updateWeightUnit($customer, new UpdateWeightUnitData(weightUnit: $customerData->weightUnit));
                break;
            case 'updateWaterVolume':
                $customer = $this->customerUpdater->updateVolumeWater($customer, new UpdateWaterVolumeData(volume: $customerData->volumeWater));
                break;
            case 'updateWaterVolumeByEach':
                $customer = $this->customerUpdater->updateVolumeWaterByEach($customer, new UpdateWaterVolumeData(volume: $customerData->volumeWater));
                break;
            case 'updateUsername':
                $customer = $this->customerUpdater->updateUsername($customer, new UpdateUsernameData(username: $customerData->username));
                break;
            case 'updateStartWeight':
                $customer = $this->customerUpdater->updateStartWeight($customer, new UpdateTargetOrStartWeightData(weight: $customerData->startWeight));
                break;
            case 'updateTargetWeight':
                $customer = $this->customerUpdater->updateWantedWeight($customer, new UpdateTargetOrStartWeightData(weight: $customerData->targetWeight));
                break;
            case 'updateSteps':
                $customer = $this->customerUpdater->updateSteps($customer, new UpdateStepsData(steps: $customerData->steps));
                break;
            case 'updateMacros':
                $customer = $this->customerUpdater->updateNutrition($customer, new UpdateNutritionData(protein: $customerData->protein, carbs: $customerData->carbs, fat: $customerData->fat));
                break;
            case 'updateKcals':
                $customer = $this->customerUpdater->updateKcal($customer, new UpdateKcalData(kcal: $customerData->kcal));
                break;
            case 'updateHeightUnit':
                $customer = $this->customerUpdater->updateHeightUnit($customer, new UpdateHeightUnitData(heightUnit: $customerData->heightUnit));
                break;
            case 'updateHebdoChange':
                $customer = $this->customerUpdater->updateHebdo($customer, new UpdateHebdoChangeData(hebdoChange: $customerData->hebdoChange));
                break;
            case 'updateHeight':
                $customer = $this->customerUpdater->updateHeight($customer, new UpdateHeightData(height: $customerData->height));
                break;
            case 'updateGoal':
                $customer = $this->customerUpdater->updateGoal($customer, new UpdateGoalData(goalId: $customerData->goalId));
                break;
            case 'updateActivity':
                $customer = $this->customerUpdater->updateActivity($customer, new UpdateActivityData(activityId: $customerData->activityId));
                break;
            case 'updateBirthDate':
                $customer = $this->customerUpdater->updateBirthDay($customer, new UpdateBirthDateData(birthDate: $customerData->birthDate));
                break;
            case 'updateGender':
                $customer = $this->customerUpdater->updateGender($customer, new UpdateGenderData(gender: $customerData->gender));
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue.'
                ], 400);
        }

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => new CustomerResource($customer)
        ]);
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

    public function handlePassword(Customer $customer, PasswordCustomerData $passwordCustomerData) : JsonResponse {
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



    public function addWater(Customer $customer, AddWaterData $addWaterData):JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        $waters = $this->customerRepository->addWaterToCustomer($customer, $addWaterData);
        return response()->json([
            'success' => true,
            'waters' => WaterResource::collection($waters)
        ]);

    }

    public function delWater(Customer $customer, DelWaterData $delWaterData):JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        $this->customerRepository->delWaterToCustomer($customer, $delWaterData);
        return response()->json([
            'success' => true,
        ]);

    }



}
