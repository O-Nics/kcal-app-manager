<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
