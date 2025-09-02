<?php

namespace App\Support\Repositories\Customer;

use App\Features\Customer\Data\AddWaterData;
use App\Features\Customer\Data\CreateProgramData;
use App\Features\Customer\Data\DelWaterData;
use App\Features\Customer\Data\PasswordCustomerData;
use App\Features\Customer\Data\RegisterCustomerData;
use App\Features\Customer\Data\WeightCustomerData;
use App\Features\Customer\Services\ProgramPlanner;
use App\Features\ProgramPlanner\Contracts\PolicyProvider;
use App\Features\ProgramPlanner\Domain\ProgramRequest;
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
use Illuminate\Http\JsonResponse;
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

//
//    public function storeCustomer(User $user, RegisterCustomerDTO $registerCustomerDTO): Customer
//    {
//        /*** @var Customer $customer */
//        $customers = $user->customers()->get();
//
//        foreach ($customers as $customer) {
//            if ($customer->username === $registerCustomerDTO->username) {
//                abort(400, "Ce profil existe déjà.");
//            }
//        }
//
//       $customer =  $user->customers()->create([
//            'username' => $registerCustomerDTO->username,
//        ]);
//
//       return $customer;
//
//
//
//    }
//
//    public function updateGeneralsInfoCustomer(Customer $customer, UpdateGeneralsInfoCustomerDTO $updateGeneralsInfoCustomerDTO): Customer
//    {
//
//
//
//        $oldPicture = $customer->customerImage()->get()->first();
//        if($oldPicture &&  ($updateGeneralsInfoCustomerDTO->picture != null || $updateGeneralsInfoCustomerDTO->deletePicture)) {
//            $this->documentRepository->delete($oldPicture);
//            if($updateGeneralsInfoCustomerDTO->deletePicture){
//                $customer->setAttribute('image_id', null);
//            }
//        }
//        if($updateGeneralsInfoCustomerDTO->picture != null){
//            $documents = $this->documentRepository->store([$updateGeneralsInfoCustomerDTO->picture], $customer);
//            $customer->setAttribute('image_id', $documents->first()->id);
//            $customer->save();
//        }
//
//
//        $birthdate = null;
//
//        if($updateGeneralsInfoCustomerDTO->birthDate) {
//            $birthdate = Carbon::createFromFormat('Y-m-d H:i:s', $updateGeneralsInfoCustomerDTO->birthDate);
//        }
//
//
//        $customer->setAttribute('username', $updateGeneralsInfoCustomerDTO->username);
//        $customer->setAttribute('birth_date', $birthdate);
//        $customer->setAttribute('phone', $updateGeneralsInfoCustomerDTO->phone);
//        $customer->setAttribute('height', $updateGeneralsInfoCustomerDTO->height);
//        $customer->setAttribute('gender', $updateGeneralsInfoCustomerDTO->gender);
//        $customer->setAttribute('activity', $updateGeneralsInfoCustomerDTO->activity);
//        $customer->setAttribute('goal', $updateGeneralsInfoCustomerDTO->goal);
//
//        if($updateGeneralsInfoCustomerDTO->weight) {
//            $weight = $customer->weights()->latest()->first();
//            if($weight && $weight->created_at->diffInDays(Carbon::now()) == 0) {
//                $weight->update([
//                    'weight' => $updateGeneralsInfoCustomerDTO->weight,
//                ]);
//            } else {
//                $customer->weights()->create([
//                    'weight' => $updateGeneralsInfoCustomerDTO->weight,
//                ]);
//            }
//        }
////        $customer->weights()->create([
////            'weight' => $updateGeneralsInfoCustomerDTO->weight ?? 0,
////        ]);
//
//        $customer->saveOrFail();
//
//        return $customer;
//    }
//
//
//    public function findCustomerById(string $id): Customer
//    {
//        return $this->query()
//            ->where('id', $id)
//            ->firstOrFail();
//    }
//
//    public function updateIntolerancesAllergiesCustomer(Customer $customer, UpdateIntolerancesAllergiesCustomerDTO $updateNutritionInfoCustomerDTO): Customer
//    {
//        $customer->setAttribute('allergies_intolerances', json_encode($updateNutritionInfoCustomerDTO->allergiesIntolerances));
//        $customer->saveOrFail();
//        return $customer;
//    }
//
//    public function updateNotification(Customer $customer, string $column, bool $value): void
//    {
//        if($customer->$column != $value) {
//            $customer->$column = $value;
//            $customer->saveOrFail();
//        }
//    }
//
//    public function deleteCustomer(Customer $customer): void
//    {
//        $customer->delete();
//    }
//
//    public function handlePasswordCustomer(Customer $customer, PasswordCustomerDTO $passwordCustomerDTO): void
//    {
//        if($passwordCustomerDTO->profileLocked){
//            $customer->profile_password = Hash::make($passwordCustomerDTO->passwordCustomer);
//            $customer->profile_locked = true;
//        } else
//        {
//            $customer->profile_password = null;
//            $customer->profile_locked = false;
//        }
//        $customer->saveOrFail();
//    }
//
//    public function addWeightCustomer(Customer $customer, AddWeightCustomerDTO $addWeightCustomerDTO): Collection
//    {
//        /** @var CustomerWeight $weight */
//        $weight = $customer->customerWeights()->latest()->first();
//
//        if ($weight && $weight->created_at->diffInDays(Carbon::now()) == 0) {
//           $weight->update([
//               'customer_weight' => $addWeightCustomerDTO->weight,
//           ]);
//        } else {
//           $weight = $customer->customerWeights()->create([
//                'customer_weight' => $addWeightCustomerDTO->weight,
//            ]);
//        }
//        $weights = $customer->customerWeights()->get();
//        return $weights;
//    }
//
//    public function addMissingAllergie(?Customer $customer, AddAllergieMissingDTO $addAllergieMissingDTO): void
//    {
//        if($customer){
//            $customer->allergieMissings()->create([
//                'name' => $addAllergieMissingDTO->name,
//            ]);
//        } else {
//           $allergieMissing = new AllergieMissing();
//            $allergieMissing->name = $addAllergieMissingDTO->name;
//        }
//    }
//
//    public function addMissingIngredient(?Customer $customer, AddIngredientMissingDTO $addIngredientMissingDTO): void
//    {
//        if($customer){
//            $customer->ingredientMissings()->create([
//                'ingredient_name' => $addIngredientMissingDTO->name,
//            ]);
//        } else {
//            $ingredientMissing = new IngredientMissing();
//            $ingredientMissing->ingredient_name = $addIngredientMissingDTO->name;
//        }
//    }
//
//    public function updateIngredientToAvoidCustomer(Customer $customer, UpdateIngredientToAvoidCustomerDTO $ingredientToAvoidCustomerDTO): Customer
//    {
//        $customer->setAttribute('food_to_avoid', json_encode($ingredientToAvoidCustomerDTO->ingredientToAvoid));
//        $customer->saveOrFail();
//        return $customer;
//    }
//
//    public function updateFoodPreferencesCustomer(Customer $customer, UpdateFoodPreferencesCustomerDTO $updateFoodPreferencesCustomerDTO): Customer
//    {
//        $customer->setAttribute('food_preferences', json_encode($updateFoodPreferencesCustomerDTO->foodPreferences));
//        $customer->saveOrFail();
//        return $customer;
//    }
//
//    public function addMissingFoodPreferences(?Customer $customer, AddFoodPreferencesMissingDTO $addFoodPreferencesMissingDTO): void
//    {
//        if($customer){
//            $customer->foodPreferencesMissings()->create([
//                'name' => $addFoodPreferencesMissingDTO->name,
//            ]);
//        } else {
//            $foodPreferences = new FoodPreferencesMissing();
//            $foodPreferences->name = $addFoodPreferencesMissingDTO->name;
//        }
//    }
//
//    public function getIngredientFromAudio(string $audio): array
//    {
//
//        $client = OpenAI::factory()
//        ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 120]))
//        ->withApiKey(env('OPENAI_API_KEY'))
//        ->make();
//
//        $audioFilePath = $audio;
//
//
//        $response = $client->audio()->transcribe([
//            'model' => 'whisper-1',
//            'file' => fopen($audioFilePath, 'r'),
//            'response_format' => 'verbose_json',
//            'timestamp_granularities' => ['segment', 'word']
//        ]);
//
//
//        $transcriptionText = $response['text'];
//
//        $unities = Unity::all();
//        $type = IngredientType::all();
//
//        $ingredientResponse = $client->chat()->create([
//            'model' => 'gpt-4o',
//            'messages' => [
//                [
//                    "role"=> "system",
//                    "content" => "Tu es un assistant qui extrait et analyse les informations nutritionnelles des ingrédients en français à partir d'un texte donné.
//                   Renvoie les ingrédients avec l'id de leur type parmi cette liste : $type, et leur sous-nom si applicable (ex. 'aile de poulet' avec sous-nom 'poulet').
//                   Corrige les noms familiers ou mal formulés en les remplaçant par les termes corrects.
//                   Formate les quantités en fonction des unités spécifiées dans $unities.
//                   Date actuelle : " . Carbon::now()->format('Y-m-d H:i:s') . "
//
//                   DLC et stockage :
//                   - Si une date est donnée, renvoie-la telle quelle.
//                   - Si une durée de conservation est mentionnée sans date précise, ajoute cette durée à la date actuelle.
//                   - Si l’ingrédient est congelé, renvoie null.
//                   - Sinon Pour chaque ingrédient par rapport au nom, ajoute une estimation de la DLC (date limite de consommation) basée sur des durées de conservation réalistes pour chaque ingrédient.
//                   - Assure-toi que la DLC soit toujours après la date actuelle et formate-la en 'Y-m-d'.
//
//                  si un ingredient est dit congelé, renvoie is_frozen a true sinon a false.
//
//                   Filtrage des éléments :
//                   - Ignore tout ce qui n’est pas lié à la cuisine (propos non culinaires, insultes, etc.).
//                   - Élimine les plats préparés ou cuisinés et toute référence à des humains.
//                   - Exclue les ingrédients mal formulés ou incomplets.
//
//                   Formate chaque nom d'ingrédient avec une majuscule et le reste en minuscule."
//                ],
//                [
//                    'role' => 'user',
//                    'content' => $transcriptionText
//                ]
//            ],
//            'response_format' => [
//                'type' => 'json_schema',
//                'json_schema' => [
//                    'name' => 'ingredient_response',
//                    'strict' => true,
//                    'schema' => [
//                        'type' => 'object',
//                        'properties' => [
//                            'ingredients' => [
//                                'type' => 'array',
//                                'items' => [
//                                    'type' => 'object',
//                                    'properties' => [
//                                        'ingredient_name' => [
//                                            'type' => 'string'
//                                        ],
//                                        'ingredient_type_id' => [
//                                            'type' => 'string'
//                                        ],
//                                        'sub_name' => [
//                                            'type' => 'string'
//                                        ],
//                                        'unity' => [
//                                            'type' => 'object',
//                                            'properties' => [
//                                                'name' => [
//                                                    'type' => 'string'
//                                                ],
//                                                'abbreviation' => [
//                                                    'type' => 'string'
//                                                ],
//                                                'code' => [
//                                                    'type' => 'string'
//                                                ]
//                                            ],
//                                            'required' => ['name', 'abbreviation', 'code'],
//                                            'additionalProperties' => false
//                                        ],
//                                        'dlc' => [
//                                            'type' => 'string'
//                                        ],
//                                        'quantity' => [
//                                            'type' => 'number'  // ex: "3 unités"
//                                        ],
//                                        'is_frozen' => [
//                                            'type' => 'boolean'
//                                        ],
//                                    ],
//                                    'required' => ['ingredient_name','ingredient_type_id', 'sub_name', 'quantity', 'is_frozen', 'unity', 'dlc'],
//                                    'additionalProperties' => false
//                                ]
//                            ]
//                        ],
//                        'required' => ['ingredients'],
//                        'additionalProperties' => false
//                    ]
//                ]
//            ]
//        ]);
////        dont work
//        if (Storage::exists($audioFilePath)) {
//            Storage::delete($audioFilePath);
//        }
//
//        // ...
//
//        $ingredients = [];
//
//        foreach ($ingredientResponse->choices as $choice) {
//            $extractedIngredients = json_decode($choice->message->content)->ingredients;
//            $count = 0;
//            foreach ($extractedIngredients as $ingredient) {
//                // Récupérer le type d'ingrédient comme ressource
//                $ingredientType = IngredientType::find($ingredient->ingredient_type_id);
//                if ($ingredientType) {
//                    $ingredients[] = [
//                        'id' => ++$count,
//                        'ingredient_name' => $ingredient->ingredient_name,
//                        'sub_name' => $ingredient->sub_name,
//                        'quantity' =>(float)$ingredient->quantity,
//                        'unity' => $ingredient->unity,
//                        'dlc' => $ingredient->dlc,
//                        'is_frozen' => (bool) $ingredient->is_frozen,
//                        'ingredient_type' => new IngredientTypeResource($ingredientType) // Utilisation de la ressource
//                    ];
//                }
//            }
//        }
//
//// Supprimer le fichier audio après utilisation
//        if (Storage::exists($audioFilePath)) {
//            Storage::delete($audioFilePath);
//        }
//
//// Retourner les ingrédients formatés
//        return $ingredients;
//        // renvoie
//    }
//
//    public function createProgramCustomer(CreateProgramCustomerDTO $createProgramCustomerDTO, Customer $customer, User $user): Customer
//    {
//        if($customer->is_owner){
//            $user->update([
//                'name' => $createProgramCustomerDTO->username,
//            ]);
//
//        }
//        $customer->update([
//            'username' => $createProgramCustomerDTO->username,
//            'gender' => $createProgramCustomerDTO->gender,
//            'goal' => $createProgramCustomerDTO->goal,
//            'height' => $createProgramCustomerDTO->height,
//            'birth_date' => $createProgramCustomerDTO->birthDate,
//            'wanted_weight' => $createProgramCustomerDTO->goalWeight,
//            'start_weight' => $createProgramCustomerDTO->startWeight,
//            'obj_water' => 2.0,
//            'activity' => $createProgramCustomerDTO->activityLevel
//        ]);
//
//        if (!$customer->customerWeights()->where('customer_weight', $createProgramCustomerDTO->startWeight)->exists()) {
//            $customer->customerWeights()->create([
//                'customer_weight' => $createProgramCustomerDTO->startWeight,
//            ]);
//        }
//
//        $program = $this->generateDailyProgram($createProgramCustomerDTO, $customer);
//        $customer->update([
//            'obj_kcals' => $program['daily_kcal'],
//            'obj_steps' => $program['daily_steps_goal'],
//            'obj_loss_weight_hebdo' => $program['weekly_weight_loss'],
//            'time_to_achieve' => $program['duration_days'],
//            'obj_proteins' => 20,
//            'obj_lipids' => 30,
//            'obj_glucides' => 50,
//            'obj_water' => $program['daily_water_goal'],
//            'type_water' => 'glass',
//            'volume_water' => $program['volume_water_by_each'],
//        ]);
//
//        $mealType = MealType::query()->get();
//
//        foreach ($mealType as $type) {
//            switch ($type->code) {
//                case 1:
//                    if($customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->exists()){
//                        $customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->update([
//                            'kcal' => $program['obj_kcal_breakfast'],
//                        ]);
//                    } else {
//                        $customer->objectiveKcalMealTypes()->create([
//                            'meal_type_id' => $type->id,
//                            'kcal' => $program['obj_kcal_breakfast'],
//                        ]);
//                    }
//                    break;
//                case 2:
//                    if($customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->exists()){
//                        $customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->update([
//                            'kcal' => $program['obj_kcal_lunch'],
//                        ]);
//                    } else {
//                        $customer->objectiveKcalMealTypes()->create([
//                            'meal_type_id' => $type->id,
//                            'kcal' => $program['obj_kcal_lunch'],
//                        ]);
//                    }
//                    break;
//                case 3:
//                    if($customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->exists()){
//                        $customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->update([
//                            'kcal' => $program['obj_kcal_snack'],
//                        ]);
//                    } else {
//                        $customer->objectiveKcalMealTypes()->create([
//                            'meal_type_id' => $type->id,
//                            'kcal' => $program['obj_kcal_snack'],
//                        ]);
//                    }
//                    break;
//                case 4:
//                    if($customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->exists()){
//                        $customer->objectiveKcalMealTypes()->where('meal_type_id', $type->id)->update([
//                            'kcal' => $program['obj_kcal_dinner'],
//                        ]);
//                    } else {
//                        $customer->objectiveKcalMealTypes()->create([
//                            'meal_type_id' => $type->id,
//                            'kcal' => $program['obj_kcal_dinner'],
//                        ]);
//                    }
//                    break;
//            }
//        }
//
//
//
//
//        return $customer;
//
//
//
//    }
//
//    public function generateDailyProgram(CreateProgramCustomerDTO $data, Customer $customer, ?float $hebdo = null)
//    {
//        /*
//         *  TODO: Prendre en compte le dernier poids enregistré pour le calcul de la perte de poids ou prise de poids hebdomadaire
//         *  TODO: modifier aussi time_to_achieve de customer
//         * */
//        // 1. Calcul du BMR (Basal Metabolic Rate)
//        $age = now()->diffInYears($data->birthDate);
//        if ($data->gender === 'male') {
//            $bmr = 88.362 + (13.397 * $data->startWeight) + (4.799 * $data->height) - (5.677 * $age);
//        } elseif ($data->gender === 'female') {
//            $bmr = 447.593 + (9.247 * $data->startWeight) + (3.098 * $data->height) - (4.330 * $age);
//        } else {
//            // Default to average if gender is "other"
//            $bmr = (88.362 + (13.397 * $data->startWeight) + (4.799 * $data->height) - (5.677 * $age)) +
//                (447.593 + (9.247 * $data->startWeight) + (3.098 * $data->height) - (4.330 * $age)) / 2;
//        }
//
//        // 2. Ajustement avec le niveau d'activité
//        $activityFactors = [
//            'sedentary' => 1.2,
//            'lightly_active' => 1.375,
//            'moderately_active' => 1.55,
//            'active' => 1.725,
//            'very_active' => 1.9,
//            'athlete' => 2.0,
//        ];
//        $tdee = $bmr * ($activityFactors[$data->activityLevel] ?? 1.2);
//
//        // 3. Définir l'objectif calorique
//        $goalAdjustment = [
//            'weight_loss' => -500,     // Déficit calorique
//            'maintain' => 0,          // Maintien
//            'weight_gain' => 500,     // Surplus calorique général
//            'gain_muscles' => 350,    // Surplus calorique modéré pour prise de muscle
//        ];
//
//        $dailyKcal = $tdee + ($goalAdjustment[$data->goal] ?? 0);
//
//        if($hebdo){
//            $dailyKcal = $tdee + $hebdo;
//        }
//
//        // 4. Durée pour atteindre l'objectif
//        $weightDifference = $data->goalWeight - $data->startWeight;
//        $weeklyRate = ($data->goal === 'weight_gain') ? 0.25 :  0.5; // kg/semaine
//        $durationDays = abs($weightDifference / $weeklyRate) * 7;
//
//        // 5. Répartition des macronutriments
//        if ($data->goal === 'gain_muscles') {
//            $proteinG = $data->startWeight * 2.2; // 2.2g/kg pour maximiser la synthèse musculaire
//            $proteinKcal = $proteinG * 4;
//
//            $lipidsKcal = $dailyKcal * 0.25; // 25% des calories pour les lipides
//            $lipidsG = $lipidsKcal / 9;
//
//            $carbsKcal = $dailyKcal - ($proteinKcal + $lipidsKcal);
//            $carbsG = $carbsKcal / 4;
//        }
//        else {
//            // Répartition standard pour les autres objectifs
//            $proteinG = $data->startWeight * 2; // 2g/kg de poids
//            $proteinKcal = $proteinG * 4;
//
//            $lipidsG = $data->startWeight * 1; // 1g/kg de poids
//            $lipidsKcal = $lipidsG * 9;
//
//            $carbsKcal = $dailyKcal - ($proteinKcal + $lipidsKcal);
//            $carbsG = $carbsKcal / 4;
//        }
//
//        // 6. Objectif de pas/jour
//        $stepGoals = [
//            'sedentary' => 2500,
//            'lightly_active' => 5000,
//            'moderately_active' => 7500,
//            'active' => 10000,
//            'very_active' => 12500,
//            'athlete' => 14000,
//        ];
//        $dailyStepsGoal = $stepGoals[$data->activityLevel] ?? 2000;
//
//        // Objectif d'eau
//        $baseWaterIntake = $data->startWeight * 0.033; // 33 ml/kg de poids corporel
//        $activityWaterAdjustment = ($data->activityLevel === 'active' || $data->activityLevel === 'very_active' || $data->activityLevel === 'athlete') ? 0.5 : 0;
//        $dailyWaterGoal = $baseWaterIntake + $activityWaterAdjustment;
//
//        // 7. Répartition calorique par repas
//        $calorieDistribution = match ($data->goal) {
//            'weight_loss' => ['breakfast' => 0.30, 'lunch' => 0.35, 'dinner' => 0.30, 'snack' => 0.05],
//            'maintain' => ['breakfast' => 0.25, 'lunch' => 0.35, 'dinner' => 0.30, 'snack' => 0.10],
//            'weight_gain' => ['breakfast' => 0.30, 'lunch' => 0.40, 'dinner' => 0.20, 'snack' => 0.10],
//            'gain_muscles' => ['breakfast' => 0.30, 'lunch' => 0.35, 'dinner' => 0.25, 'snack' => 0.10],
//            default => ['breakfast' => 0.25, 'lunch' => 0.35, 'dinner' => 0.30, 'snack' => 0.10],
//        };
//
//        // Calcul des calories par repas
//        $objKcalBreakfast = round($dailyKcal * $calorieDistribution['breakfast']);
//        $objKcalLunch = round($dailyKcal * $calorieDistribution['lunch']);
//        $objKcalDinner = round($dailyKcal * $calorieDistribution['dinner']);
//        $objKcalSnack = round($dailyKcal * $calorieDistribution['snack']);
//
//        $customer->form_passed_at = Carbon::now();
//        $customer->save();
//        // 8. Résultat
//        return [
//            'daily_kcal' => round($dailyKcal),
//            'macronutrients' => [
//                'protein' => ['g' => round($proteinG), 'kcal' => round($proteinKcal)],
//                'lipids' => ['g' => round($lipidsG), 'kcal' => round($lipidsKcal)],
//                'carbs' => ['g' => round($carbsG), 'kcal' => round($carbsKcal)],
//            ],
//            'duration_days' => $data->goal === 'maintain' || $data->goal === 'gain_muscles' ? null : round($durationDays),
//            'weekly_weight_loss' => $data->goal === 'maintain' ? null : $weeklyRate,
//            'daily_steps_goal' => $dailyStepsGoal,
//            'daily_water_goal' => (round($dailyWaterGoal * 2) / 2) * 1000, // Converti en ml
//            'volume_water_by_each' => 250,
//            'obj_kcal_breakfast' => $objKcalBreakfast,
//            'obj_kcal_lunch' => $objKcalLunch,
//            'obj_kcal_dinner' => $objKcalDinner,
//            'obj_kcal_snack' => $objKcalSnack,
//        ];
//    }
//    public function firstFormUpdateProfileCustomer(Customer $customer, bool $value): Customer
//    {
//        $customer->update([
//            'filled_first_form' => $value,
//        ]);
//
//        return $customer;
//    }
//
//    public function addWaterToCustomer(AddWaterCustomerDTO $addWaterCustomerDTO, Customer $customer): Collection
//    {
//        /** @var WaterCustomer $water */
//        $waters = collect();
//        for ($i = 0; $i < $addWaterCustomerDTO->nbToFill; $i++) {
//            $water = $customer->waterCustomers()->create([
//                'volume' => $addWaterCustomerDTO->volume,
//                'added_at' => $addWaterCustomerDTO->addedAt,
//            ]);
//            $waters->push($water);
//        }
//
//        return $waters;
//    }
//
//    public function delWaterToCustomer(DelWaterCustomerDTO $delWaterCustomerDTO, Customer $customer): void
//    {
//        $customer->waterCustomers()->whereIn('id', $delWaterCustomerDTO->waterIds)->delete();
//    }
//
//    public function getCalendarCustomer(Customer $customer): Collection
//    {
//        $caloriesByDate = CustomerFood::with('foodable')
//            ->where('customer_id', $customer->id)
//            ->get()
//            ->groupBy(function ($item) {
//                return $item->added_at;
//            })
//            ->map(function ($group, $date) {
//                $totalCalories = $group->sum(function ($item) {
//                    $kcalFor100 = $item->foodable->kcal_for_100 ?? 0;
//                    return ($item->quantity / 100) * $kcalFor100;
//                });
//
//                return [
//                    'date' => $date,
//                    'kcal' => round($totalCalories, 2)
//                ];
//            })
//            ->values();
//        return collect($caloriesByDate);
//
//    }
//
//    public function getGraphicHome(Customer $customer): Collection
//    {
//        // Date limite : 7 jours avant aujourd'hui, sans inclure aujourd'hui
//        $sevenDaysAgo = now()->subDays(7)->startOfDay();
//        $yesterday = now()->subDay()->endOfDay();
//
//        // Générer toutes les dates entre sevenDaysAgo et yesterday
//        $allDates = collect();
//        for ($date = $sevenDaysAgo; $date->lte($yesterday); $date->addDay()) {
//            $allDates->push($date->copy()->toDateString()); // Assurez-vous du format YYYY-MM-DD
//        }
//
//        $sevenDaysAgo = now()->subDays(7)->startOfDay();
//        $yesterday = now()->subDay()->endOfDay();
//        // Récupérer les calories par date
//        $caloriesByDate = CustomerFood::with('foodable')
//            ->where('customer_id', $customer->id)
//            ->whereBetween('added_at', [$sevenDaysAgo, $yesterday])
//            ->get()
//            ->groupBy(function ($item) {
//                return Carbon::create($item->added_at)->toDateString(); // Utiliser toDateString pour les clés
//            })
//            ->map(function ($group, $date) {
//                $totalCalories = $group->sum(function ($item) {
//                    $kcalFor100 = $item->foodable->kcal_for_100 ?? 0;
//                    return ($item->quantity / 100) * $kcalFor100;
//                });
//
//                return [
//                    'date' => $date,
//                    'kcal' => round($totalCalories, 2),
//                ];
//            });
//
//        // Récupérer le volume d'eau consommée par date
//        $waterByDate = $customer->waterCustomers()
//            ->whereBetween('added_at', [$sevenDaysAgo, $yesterday])
//            ->get()
//            ->groupBy(function ($item) {
//                return Carbon::create($item->added_at)->toDateString(); // Utiliser toDateString pour les clés
//            })
//            ->map(function ($group, $date) {
//                $totalVolume = $group->sum('volume'); // Somme des volumes en ml
//                return [
//                    'date' => $date,
//                    'volume_ml' => round($totalVolume, 2)
//                ];
//            });
//
//        // Fusionner les données des calories et du volume d'eau par date
//        $mergedData = $allDates->map(function ($date) use ($caloriesByDate, $waterByDate) {
//            $caloriesData = $caloriesByDate->get($date, ['kcal' => 0]); // Calories par défaut si pas de donnée
//            $waterData = $waterByDate->get($date, ['volume_ml' => 0]); // Volume par défaut si pas de donnée
//
//            return array_merge(['date' => $date], $caloriesData, $waterData);
//        });
//
//        return $mergedData;
//    }
//
//
//    public function updateGoal(Customer $customer, UpdateGoalDTO $updateGoalDTO): array
//    {
//        $customer->update([
//            'goal' => $updateGoalDTO->goal,
//        ]);
//
//        $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer),$customer);
//
//        $customer->update([
//            'obj_loss_weight_hebdo' => $array['weekly_weight_loss'],
//            'obj_proteins' => $array['macronutrients']['protein']['g'],
//            'obj_lipids' => $array['macronutrients']['lipids']['g'],
//            'obj_glucides' => $array['macronutrients']['carbs']['g'],
//        ]);
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $array['weekly_weight_loss'],
//        ];
//
//    }
//
//    public function updateActivity(Customer $customer, UpdateActivityDTO $updateActivityDTO): array
//    {
//        $customer->update([
//            'activity' => $updateActivityDTO->activity,
//        ]);
//
//        $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer), $customer);
//
//        $customer->update([
//            'obj_loss_weight_hebdo' => $array['weekly_weight_loss'],
//            'obj_proteins' => $array['macronutrients']['protein']['g'],
//            'obj_lipids' => $array['macronutrients']['lipids']['g'],
//            'obj_glucides' => $array['macronutrients']['carbs']['g'],
//        ]);
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $array['weekly_weight_loss'],
//        ];
//    }
//
//    public function updateStartWeight(Customer $customer, UpdateStartWeightDTO $updateStartWeightDTO): array
//    {
//        $customer->update([
//            'start_weight' => $updateStartWeightDTO->startWeight,
//        ]);
//
//        $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer),$customer);
//
//        $customer->update([
//            'obj_loss_weight_hebdo' => $array['weekly_weight_loss'],
//        ]);
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $array['weekly_weight_loss'],
//        ];
//    }
//
//    public function updateWantedWeight(Customer $customer, UpdateWantedWeightDTO $updateWantedWeightDTO): array
//    {
//        $customer->update([
//            'wanted_weight' => $updateWantedWeightDTO->wantedWeight,
//        ]);
//
//        $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer), $customer);
//
//        $customer->update([
//            'obj_loss_weight_hebdo' => $array['weekly_weight_loss'],
//        ]);
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $array['weekly_weight_loss'],
//        ];
//    }
//
//    public function updateHebdo(Customer $customer, UpdateHebdoDTO $updateHebdoDTO): array
//    {
//        $customer->update([
//            'obj_loss_weight_hebdo' => $updateHebdoDTO->hebdo,
//        ]);
//
//        $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer),$customer, $updateHebdoDTO->hebdo);
//
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $array['weekly_weight_loss'],
//        ];
//    }
//
//    public function updateKcal(Customer $customer, UpdateKcalDTO $updateKcalDTO): array
//    {
//        if($updateKcalDTO->kcal != null){
//            $customer->update([
//                'obj_kcals' => $updateKcalDTO->kcal,
//            ]);
//            $array['daily_kcal'] = $updateKcalDTO->kcal;
//        } else {
//            $array = $this->generateDailyProgram(CreateProgramCustomerDTO::fromCustomer($customer),$customer);
//        }
//
//
//
//
//        return [
//            'daily_kcal' => $array['daily_kcal'],
//            'weekly_weight_loss' => $updateKcalDTO->kcal != null ? null : $array['weekly_weight_loss'],
//        ];
//    }
//
//    public function updateNutrition(Customer $customer, UpdateNutritionDTO $updateNutritionDTO): void
//    {
//       $customer->update([
//           'obj_proteins' => $updateNutritionDTO->proteins,
//           'obj_lipids' => $updateNutritionDTO->lipids,
//           'obj_glucides' => $updateNutritionDTO->glucids,
//       ]);
//
//
//    }
//
//    public function updateSteps(Customer $customer, UpdateStepsDTO $updateStepsDTO): void
//    {
//        $customer->update([
//            'obj_steps' => $updateStepsDTO->steps,
//        ]);
//    }
//
//    public function updateVolumeWater(Customer $customer, UpdateVolumeWaterDTO $updateVolumeWaterDTO): void
//    {
//        $customer->update([
//            'volume_water' => $updateVolumeWaterDTO->volumeWater,
//        ]);
//    }


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

        $customer->weights()->create([
            'weight' => $createProgramData->startWeight,
        ]);



        return $this->planAndAddToCustomer($createProgramData, $customer);

    }

    public function planAndAddToCustomerV1(CreateProgramData $createProgramData, Customer $customer): Customer
    {
        // LEGACY v1 path — keep old behavior
        $planner = new \App\Features\Customer\Services\ProgramPlanner();
        $program = $planner->build($createProgramData);
        dd($program);
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
        $customer->obj_weight_hebdo_change = $program->meta->weekly_kg ??  null;
        $customer->max_weight_hebdo_change = $program->meta->max_weekly_kg ?? null;
        $customer->time_to_achieve = (int)  $program->duration_days ?? 0;
        $customer->obj_proteins =   $program->macro_ratios->protein;
        $customer->obj_lipids =  $program->macro_ratios->fat;
        $customer->obj_glucides =  $program->macro_ratios->carbs;
        $customer->obj_water = (int) $program->daily_water_goal_ml;
        $customer->type_water = 'glass';
        $customer->volume_water_by_each = 250;
        $customer->train_kcal = $program->meta->train_kcal ?? null;
        $customer->rest_kcal = $program->meta->rest_kcal ?? null;
        $customer->form_passed_at = Carbon::now();
        $customer->save();

        $mealTypes = MealType::query()->get();
        $start = Carbon::now()->startOfDay();

        return $this->updateKcalPerMeal($customer, $program->per_meal_kcal);
    }

    public function planAndAddToCustomer(CreateProgramData $createProgramData, Customer $customer): Customer
    {
        // NEW v2 path — uses DI + ProgramRequest mapping
        $planner = app(\App\Features\ProgramPlanner\ProgramPlanner::class);
        $policy  = app(PolicyProvider::class);

        $goalModel = Goal::query()->find($createProgramData->goalId);
        $goalKeyRaw = $goalModel->value ?? $goalModel->code ?? 'maintain';

        $age = Carbon::parse($createProgramData->birthDate)->age;
        $heightM = $createProgramData->height / 100;
        $bmi = $heightM > 0 ? ($createProgramData->startWeight / ($heightM * $heightM)) : 0.0;

        $mealUse = [
            'breakfast'       => true,
            'snack_morning'   => true,
            'lunch'           => true,
            'snack_afternoon' => true,
            'dinner'          => true,
        ];
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
            mealUse: $mealUse,
            mealProfile: $mealProfile,
            trainDays: $createProgramData->trainingDaysOfWeek,
            hydrationMlPerKg: null,
        );

        $program = $planner->build($request);
        dd($program);


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
        $customer->obj_lipids   = $program->macro_ratios->fat;
        $customer->obj_glucides = $program->macro_ratios->carbs;
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
    public function updateNotification(Customer $customer, string $column, bool $value): void
    {
        if($customer->$column != $value) {
            $customer->$column = $value;
            $customer->saveOrFail();
        }
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
        $weight = $customer->weights()->latest()->first();

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
