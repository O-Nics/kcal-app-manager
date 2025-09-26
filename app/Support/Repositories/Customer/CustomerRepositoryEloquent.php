<?php

namespace App\Support\Repositories\Customer;

use App\Features\Customer\Data\AddWaterData;
use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\DelWaterData;
use App\Features\Customer\Data\PasswordCustomerData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Features\Customer\Data\WeightCustomerData;
use App\Features\ProgramPlanner\Nutrition\Domain\ProgramPlan;
use App\Features\ProgramPlanner\Nutrition\Domain\ProgramRequest;
use App\Features\ProgramPlanner\Nutrition\Policy\PolicyProvider;
use App\Features\ProgramPlanner\ProgramPlanner;
use App\Models\ActivityLevel;
use App\Models\BodyMass;
use App\Models\Customer;
use App\Models\Goal;
use App\Models\MealType;
use App\Models\User;
use App\Models\Water;
use App\Models\WaterCustomer;
use App\Support\Repositories\BaseRepository;
use App\Support\Repositories\Document\DocumentRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Throwable;


class CustomerRepositoryEloquent extends BaseRepository implements CustomerRepository {

    protected string $model = Customer::class;

    private DocumentRepository $documentRepository;

    public function __construct()
    {
        $this->documentRepository = resolve(DocumentRepository::class);
    }


    public function findById(string $id): ?Customer
    {
        return $this->query()->where('id', $id)
            ->first();
    }


    public function createProgramCustomer(CreateProgramData $createProgramData, ?Customer $customer, User $user): Customer
    {

        if($customer->is_owner){
            $user->update([
                'name' => $createProgramData->username,
                'username' => $createProgramData->username,
                'weight_unit' => $createProgramData->weightUnit,
                'height_unit' => $createProgramData->heightUnit,
            ]);
        }

        $customer->bodyMasses()->create([
            'weight' => $createProgramData->startWeight,
        ]);

        return $this->planAndAddToCustomer($createProgramData, $customer);

    }

    public function planAndAddToCustomer(CreateProgramData $createProgramData, Customer $customer): Customer
    {
        // NEW v2 path — uses DI + ProgramRequest mapping
        $planner = app(ProgramPlanner::class);
        $policy  = app(PolicyProvider::class);

        $goalModel = Goal::query()->find($createProgramData->goalId);
        $goalKeyRaw = $goalModel->value ?? $goalModel->code ?? 'maintain';

        $age = Carbon::parse($createProgramData->birthDate)->age;
        $heightM = $createProgramData->height / 100;
        $bmi = $heightM > 0 ? ($createProgramData->startWeight / ($heightM * $heightM)) : 0.0;


        $mealProfile = $policy->mealProfileFor(is_string($goalKeyRaw) ? $goalKeyRaw : (string)$goalKeyRaw);
        $request = new ProgramRequest(
            goal: (string)$goalKeyRaw,
            activityLevelId: (int)$createProgramData->activityLevelId,
            weight: (float)$createProgramData->startWeight,
            height: (float)$createProgramData->height,
            age: (int)$age,
            sex: $createProgramData->gender,
            bmi: (float)$bmi,
            startWeight: (float)$createProgramData->startWeight,
            targetWeight: $createProgramData->targetWeight,
            weeklyKg: $createProgramData->hebdoChange ?? null,
            mealUse: $createProgramData->mealUse,
            mealProfile: $mealProfile,
            trainDays: $createProgramData->trainingDaysOfWeek,
            hydrationMlPerKg: null,
        );

        $program = $planner->plan($request);


        $customer->gender = $createProgramData->gender;

        if (Goal::query()->find($createProgramData->goalId)) {
            $customer->goal()->associate(Goal::query()->find($createProgramData->goalId));
        } else {
            $customer->goal()->associate(Goal::query()->where('code', '2')->first());
        }

        if (ActivityLevel::query()->find($createProgramData->activityLevelId)) {
            $customer->activityLevel()->associate(ActivityLevel::query()->find($createProgramData->activityLevelId));
        } else {
            $customer->activityLevel()->associate(ActivityLevel::query()->where('code', '1')->first());
        }

        $customer->height = $createProgramData->height;
        $customer->birth_date = $createProgramData->birthDate;
        $customer->wanted_weight = $createProgramData->targetWeight;
        $customer->start_weight = $createProgramData->startWeight;
        $customer->obj_kcals = $program->daily_kcal;
        $customer->training_days_of_week = $program->training_days ?? null;
        $customer->obj_steps = $program->daily_steps_goal;
        $customer->obj_weight_hebdo_change = $program->meta->weekly_kg ?? null;
        $customer->max_weight_hebdo_change = $program->meta->max_weekly_kg ?? null;
        $customer->time_to_achieve = (int) ($program->duration_days ?? 0);
        $customer->obj_proteins = $program->macro_ratios->protein;
        $customer->obj_fat   = $program->macro_ratios->fat;
        $customer->obj_carbs = $program->macro_ratios->carbs;
        $customer->obj_water = (int) $program->daily_water_goal_ml;
        $customer->type_water = 'glass';
        $customer->volume_water_by_each = 250;
        $customer->train_kcal = $program->meta->train_kcal ?? null;
        $customer->rest_kcal  = $program->meta->rest_kcal ?? null;
        $customer->form_passed_at = Carbon::now();
        $customer->save();

        return $this->updateKcalPerMeal($customer, $program->per_meal_kcal);
    }

    public function updateKcalPerMeal(Customer $customer, array $kcalPerMeal): Customer
    {
        $mealType = MealType::query()->get();

        foreach ($mealType as $type ) {
            /** @var MealType $type */
            $start = Carbon::now()->startOfDay();

            // Utilise directement la valeur de la relation (ex: 'breakfast', 'lunch', ...)
            $key = $type->value;

            if (!isset($kcalPerMeal[$key])) { continue; }
            // 1) Clôturer l'actif s'il existe (ends_at = NULL)
            $activeQuery = $customer->objectiveKcalMealTypes()
                ->where('meal_type_id', $type->id)
                ->whereNull('ends_at');

            if ($activeQuery->exists()) {
                $activeQuery->update(['ends_at' => $start]);
            }

            // 2) S'il existe déjà une ligne pour aujourd'hui → on la met à jour et on la rouvre (ends_at = null)
            $todayQuery = $customer->objectiveKcalMealTypes()
                ->where('meal_type_id', $type->id)
                ->where('starts_at', '=', $start);

            if ($todayQuery->exists()) {
                $todayQuery->update([
                    'kcal' => (int) $kcalPerMeal[$key],
                    'starts_at' => $start,
                    'ends_at' => null,
                ]);

            } else {
                // 3) Sinon on crée une nouvelle ligne à partir d'aujourd'hui
                $customer->objectiveKcalMealTypes()->create([
                    'meal_type_id' => $type->id,
                    'kcal' => (int) $kcalPerMeal[$key],
                    'starts_at' => $start,
                ]);
            }
        }

        return $customer;
    }
    public function storeCustomer(User $user, RegisterCustomerData $registerCustomerData): Customer
    {
        $customers = $user->customers;
        foreach ($customers as $customer) {
            if ($customer->username === $registerCustomerData->username) {
                abort(400, "Ce profil existe déjà.");
            }
        }

        return $user->customers()->create([
            'username' => $registerCustomerData->username
        ]);

    }

    /**
     * @throws Throwable
     */
    public function updateNotification(Customer $customer, string $column, bool $value): Customer
    {
        if($customer->$column != $value) {
            $customer->$column = $value;
            $customer->saveOrFail();
        }

        return $customer;
    }

    /**
     * @throws \Exception
     */
    public function deleteCustomer(Customer $customer): void
    {
        if ($customer->is_owner) {
            abort(401,"Impossible de supprimer le profil du propriétaire.");
        }

        $customer->delete();
    }

    /**
     * @throws Throwable
     */
    public function handlePasswordCustomer(Customer $customer, PasswordCustomerData $passwordCustomer) : void
    {
        if($passwordCustomer->profileLocked && $passwordCustomer->passwordCustomer){
            $customer->profile_password = Hash::make($passwordCustomer->passwordCustomer);
            $customer->profile_locked = true;
        } else
        {
            $customer->profile_password = null;
            $customer->profile_locked = false;
        }
        $customer->saveOrFail();
    }

    public function addWeight(Customer $customer, WeightCustomerData $weightCustomerData)
    {
        $weight = $customer->bodyMasses()->latest()->first();

        if ($weight && $weight->created_at->diffInDays(Carbon::now()) < 1) {
            $weight->update([
                'weight' => $weightCustomerData->weight,
                'waist' => $weightCustomerData->waist,
                'hip' => $weightCustomerData->hip,
                'chest' => $weightCustomerData->chest,
                'arm' => $weightCustomerData->arm,
                'thigh' => $weightCustomerData->thigh,
            ]);
            /** @var BodyMass $weight */
            $weight->load('files');

            foreach ($weight->files as $document) {
                if (!in_array($document->id, $weightCustomerData->documentIds ?? [])) {
                    $this->documentRepository->delete($document);
                }
            }

        } else {
            $weight = $customer->customerWeights()->create([
                'weight' => $weightCustomerData->weight,
                'waist' => $weightCustomerData->waist,
                'hip' => $weightCustomerData->hip,
                'chest' => $weightCustomerData->chest,
                'arm' => $weightCustomerData->arm,
                'thigh' => $weightCustomerData->thigh,
            ]);

        }

        $this->documentRepository->store($weightCustomerData->files, $weight, 'weight', $customer->id);

        return $customer->customerWeights()->get();
    }

    public function updateOneColumn(string $column, mixed $value, Customer $customer): Customer
    {
        if($column === "id" || $column === "user_id" || $column === "is_owner"){
            abort(400, "Modification de cette colonne impossible.");
        }

        if ($customer->$column != $value) {
            $customer->$column = $value;
            $customer->save();
        }

        return $customer;
    }


    public function addWaterToCustomer(Customer $customer, AddWaterData $addWaterData): Collection
    {
        /** @var Water $water */
        $waters = collect();
        for ($i = 0; $i < $addWaterData->nbToFill; $i++) {
            $water = $customer->waterCustomers()->create([
                'volume' => $addWaterData->volume,
                'added_at' => $addWaterData->addedAt,
            ]);
            $waters->push($water);
        }

        return $waters;
    }

    public function delWaterToCustomer(Customer $customer, DelWaterData $delWaterData)
    {
        $customer->waterCustomers()->whereIn('id', $delWaterData->waterIds)->delete();
    }
}
