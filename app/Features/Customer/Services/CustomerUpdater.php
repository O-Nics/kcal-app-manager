<?php

namespace App\Features\Customer\Services;

use AllowDynamicProperties;
use App\Features\Customer\Data\CreateProgramData;
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
use App\Models\Customer;
use App\Support\Repositories\Customer\CustomerRepository;
use Illuminate\Http\JsonResponse;

#[AllowDynamicProperties] class CustomerUpdater
{
    public function __construct()
    {
        $this->customerRepository = app(CustomerRepository::class);

    }
    public function updateGoal(Customer $customer, UpdateGoalData $updateGoalData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('goal_id', $updateGoalData->goalId, $customer);

        return $this->updateProgramCustomer($customer);

    }

    public function updateActivity(Customer $customer, UpdateActivityData $updateActivityData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('activity_level_id', $updateActivityData->activityId, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateStartWeight(Customer $customer, UpdateTargetOrStartWeightData $updateStartWeightData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('start_weight', $updateStartWeightData->weight, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateWantedWeight(Customer $customer, UpdateTargetOrStartWeightData $updateStartWeightData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('wanted_weight', $updateStartWeightData->weight, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateHebdo(Customer $customer, UpdateHebdoChangeData $updateHebdoChangeData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('obj_weight_hebdo_change', $updateHebdoChangeData->hebdoChange, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateKcal(Customer $customer, UpdateKcalData $updateKcalData): Customer
    {
        $mealTypeArray = $customer->getMealTypeAsArray();
        $goal = $customer->goal->value;

        $prog = new OldProgramPlanner();

        $kcalPerMeal = $prog->distributeByMealUse($updateKcalData->kcal, $mealTypeArray,$prog->getBaseProfile($goal));

        $customer = $this->customerRepository->updateKcalPerMeal($customer, $kcalPerMeal);

        return $this->customerRepository->updateOneColumn('obj_kcals', $updateKcalData->kcal, $customer);
    }

    public function updateNutrition(Customer $customer, UpdateNutritionData $nutritionData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('obj_proteins', $nutritionData->protein, $customer);
        $customer = $this->customerRepository->updateOneColumn('obj_lipids', $nutritionData->fat, $customer);
        return $this->customerRepository->updateOneColumn('obj_glucides', $nutritionData->carbs, $customer);
    }

    public function updateSteps(Customer $customer, UpdateStepsData $updateStepsData): Customer
    {
        return $this->customerRepository->updateOneColumn('obj_steps', $updateStepsData->steps, $customer);
    }

    public function updateVolumeWaterByEach(Customer $customer, UpdateWaterVolumeData $updateWaterVolumeData): Customer
    {
        return $this->customerRepository->updateOneColumn('volume_water_by_each', $updateWaterVolumeData->volume, $customer);
    }

    public function updateVolumeWater(Customer $customer, UpdateWaterVolumeData $updateWaterVolumeData): Customer
    {
        return $this->customerRepository->updateOneColumn('obj_water', $updateWaterVolumeData->volume, $customer);
    }

    public function updateUsername(Customer $customer, UpdateUsernameData $usernameData): Customer
    {
        return $this->customerRepository->updateOneColumn('username', $usernameData->username, $customer);
    }

    public function updateHeight(Customer $customer, UpdateHeightData $updateHeightData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('height', $updateHeightData->height, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateBirthDay(Customer $customer, UpdateBirthDateData $updateBirthDateData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('birth_date', $updateBirthDateData->birthDate, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateGender(Customer $customer, UpdateGenderData $updateGenderData): Customer
    {
        $customer = $this->customerRepository->updateOneColumn('gender', $updateGenderData->gender, $customer);

        return $this->updateProgramCustomer($customer);
    }

    public function updateWeightUnit(Customer $customer, UpdateWeightUnitData $updateWeightUnitData): Customer
    {
        return $this->customerRepository->updateOneColumn('weight_unit', $updateWeightUnitData->weightUnit, $customer);
    }

    public function updateHeightUnit(Customer $customer, UpdateHeightUnitData $updateHeightUnitData): Customer
    {
        return $this->customerRepository->updateOneColumn('height_unit', $updateHeightUnitData->heightUnit, $customer);
    }



    public function updateProgramCustomer(Customer $customer) : Customer
    {
        $createProgramData = CreateProgramData::fromCustomer($customer);

        return $this->customerRepository->planAndAddToCustomer($createProgramData, $customer);
    }

    public function updateNotification(Customer $customer, UpdateNotificationData $updateNotificationData): Customer
    {
        return $this->customerRepository->updateNotification($customer, $updateNotificationData->notificationColumn, $updateNotificationData->notificationValue);
    }
}
