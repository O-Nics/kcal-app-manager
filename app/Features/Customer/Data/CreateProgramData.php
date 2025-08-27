<?php

namespace App\Features\Customer\Data;

use App\Models\Customer;
use App\Support\Data\DeviceData;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

class CreateProgramData extends Data
{

    #[Required]
    public string $username;

    #[Required]
    public float $height;

    #[Required]
    public float $startWeight;

    #[Required]
    public float $targetWeight;

    #[Required]
    public string $gender;

    #[Required]
    public string $goalId;
    // weight loss  perdre du poids en générale : code 1
    //weight gain prise de masse : code 2
    //maintain weight maintenir son poids : code 3
    // gain muscles prise de masse musculaire : code 4


    #[Required]
    public string $activityLevelId;
    //
    //bedrest Convalescence / alité repos prolongé 1.10 code 1
    //sedentary Sédentaire (peu actif) bureau, < 5k pas/jour 1.20 code 2
    //light_walk Léger (marche quotidienne) 5–7k pas/jour 1.35 code 3
    //light_sport Léger + sport 1–2x/semaine yoga, vélo tranquille 1.45 code 4
    //moderate Modéré 7–10k pas/jour 1.55 code 5
    //moderate_sport Modéré + sport 3–4x/semaine muscu/cardio modéré 1.60 code 6
    //active Actif >10k pas/jour 1.70 code 7
    //very_active Très actif / entraînement quasi quotidien sportifs réguliers 1.85 code 8

    #[Required]
    #[WithCast(DateTimeInterfaceCast::class, format: 'd-m-Y')]
    public ?Carbon $birthDate = null;

    #[Required]
    public float $hebdoChange; // Weekly weight loss goal in kg

    #[Required]
    public string $heightUnit = 'cm'; // 'cm' or 'in'

    #[Required]
    public string $weightUnit = 'kg'; // 'kg' or 'lb'

    #[Required]
    public array $mealUse;


    public ?array $trainingDaysOfWeek = null; // Number of training days per week, if applicable
    // tableau avec breackfast, lunch, dinner, snacks ou only  lunch, dinner, snacks etc


    /**
     * Crée un CreateProgramData à partir d'un Customer + overrides explicites.
     * Seuls quelques champs peuvent être inférés; les autres (obligatoires) doivent être fournis dans $overrides.
     * Exemple d'appel :
     *   CreateProgramData::fromCustomer($customer, [
     *     'height' => 185,
     *     'startWeight' => 100,
     *     'targetWeight' => 80,
     *     'gender' => 'male',
     *     'goalId' => '1',
     *     'activityLevelId' => '3',
     *     'hebdoLoss' => 0.5,
     *     'mealUse' => ['breakfast','lunch','dinner'],
     *     'trainingDaysOfWeek' => 3,
     *   ]);
     */
    public static function fromCustomer(Customer $customer, array $overrides = []): self
    {
        $base = [
            'username' => $customer->username ?? '',
            'height' => $customer->height ?? 0.0,
            'startWeight' => $customer->start_weight ?? 0.0,
            'targetWeight' => $customer->wanted_weight ?? 0.0,
            'gender' => $customer->gender ?? 'other',
            'goalId' => $customer->goal->id ?? 1,
            'activityLevelId' => $customer->activityLevel->id ?? 1,
            'birthDate' => $customer->birth_date
                ? (\is_string($customer->birth_date) ? Carbon::parse($customer->birth_date) : $customer->birth_date)
                : null,
            'hebdoChange' => $customer->obj_weight_hebdo_change ?? 0.0,
            'mealUse' => $customer->getMealTypeAsArray() ?? ['breakfast', 'lunch', 'dinner'],
            'trainingDaysOfWeek' => $customer->training_days_of_week ?? null,

        ];

        return self::from(array_merge($base, $overrides));
    }

}
