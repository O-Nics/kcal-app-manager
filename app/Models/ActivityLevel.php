<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ActivityLevel
 *
 * Represents different levels of physical activity with associated multipliers.
 *
 * @property string $id The name of the activity level.
 * @property string $name The name of the activity level.
 * @property string $code A unique code for the activity level.
 * @property float $multiplier The multiplier associated with the activity level.
 */
class ActivityLevel extends Model
{
    //bedrest Convalescence / alité repos prolongé1.10
    //sedentary Sédentaire (peu actif)
    //bureau, < 5k pas/jour 1.20
    //light_walk Léger (marche quotidienne) 5–7k pas/jour 1.35
    //light_sport Léger + sport 1–2x/semaine yoga, vélo tranquille 1.45
    //moderate Modéré 7–10k pas/jour 1.55
    //moderate_sport Modéré + sport 3–4x/semaine muscu/cardio modéré 1.60
    //active Actif >10k pas/jour 1.70
    //very_active Très actif / entraînement quasi quotidien sportifs réguliers 1.85

    protected $fillable = [
        'name',
        'code',
        'multiplier',
    ];

    public function customers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Customer::class, 'activity_level_id');
    }
}
