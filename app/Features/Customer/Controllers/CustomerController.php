<?php

namespace App\Features\Customer\Controllers;

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
use App\Features\Customer\Services\ProgramPlanner;
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

        $customer = $this->customerRepository->updateOneColumn('goal_id', $updateGoalData->goalId, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateActivity(Customer $customer, UpdateActivityData $updateActivityData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('activity_level_id', $updateActivityData->activityId, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateStartWeight(Customer $customer, UpdateTargetOrStartWeightData $updateStartWeightData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('start_weight', $updateStartWeightData->weight, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateWantedWeight(Customer $customer, UpdateTargetOrStartWeightData $updateStartWeightData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('wanted_weight', $updateStartWeightData->weight, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateHebdo(Customer $customer, UpdateHebdoChangeData $updateHebdoChangeData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('obj_weight_hebdo_change', $updateHebdoChangeData->hebdoChange, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateKcal(Customer $customer, UpdateKcalData $updateKcalData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $mealTypeArray = $customer->getMealTypeAsArray();
        $goal = $customer->goal->value;

        $prog = new ProgramPlanner();

        $kcalPerMeal = $prog->distributeByMealUse($updateKcalData->kcal, $mealTypeArray,$prog->getBaseProfile($goal));

        $customer = $this->customerRepository->updateKcalPerMeal($customer, $kcalPerMeal);

        $customer = $this->customerRepository->updateOneColumn('obj_kcals', $updateKcalData->kcal, $customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

     public function updateNutrition(Customer $customer, UpdateNutritionData $nutritionData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('obj_proteins', $nutritionData->protein, $customer);
        $customer = $this->customerRepository->updateOneColumn('obj_lipids', $nutritionData->fat, $customer);
        $customer = $this->customerRepository->updateOneColumn('obj_glucides', $nutritionData->carbs, $customer);


        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateSteps(Customer $customer, UpdateStepsData $updateStepsData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('obj_steps', $updateStepsData->steps, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateVolumeWaterByEach(Customer $customer, UpdateWaterVolumeData $updateWaterVolumeData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('volume_water_by_each', $updateWaterVolumeData->volume, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateVolumeWater(Customer $customer, UpdateWaterVolumeData $updateWaterVolumeData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('obj_water', $updateWaterVolumeData->volume, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateUsername(Customer $customer, UpdateUsernameData $usernameData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('username', $usernameData->username, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateHeight(Customer $customer, UpdateHeightData $updateHeightData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('height', $updateHeightData->height, $customer);

        $customer = $this->updateProgramCustomer($customer);

        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateBirthDay(Customer $customer, UpdateBirthDateData $updateBirthDateData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('birth_date', $updateBirthDateData->birthDate, $customer);

        $customer = $this->updateProgramCustomer($customer);


        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateGender(Customer $customer, UpdateGenderData $updateGenderData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('gender', $updateGenderData->gender, $customer);

        $customer = $this->updateProgramCustomer($customer);


        return response()->json([
            'success' => true,
            'updated_plan' => true,
            'customer' => $customer
        ]);

    }

    public function updateWeightUnit(Customer $customer, UpdateWeightUnitData $updateWeightUnitData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('weight_unit', $updateWeightUnitData->weightUnit, $customer);


        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }

    public function updateHeightUnit(Customer $customer, UpdateHeightUnitData $updateHeightUnitData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $customer = $this->customerRepository->updateOneColumn('height_unit', $updateHeightUnitData->heightUnit, $customer);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);

    }



    private function updateProgramCustomer(Customer $customer) : Customer
    {
        $createProgramData = CreateProgramData::fromCustomer($customer);

        return $this->customerRepository->planAndAddToCustomer($createProgramData, $customer);
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
