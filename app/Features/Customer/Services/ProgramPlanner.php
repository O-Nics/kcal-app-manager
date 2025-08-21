<?php

namespace App\Features\Customer\Services;

use App\Features\Customer\Data\CreateProgramData;
use Carbon\Carbon;

final class ProgramPlanner
{
    // Map tes ids -> codes internes (adapte à ta base si besoin)
    private const array GOAL_MAP = [
        '1' => 'weight_loss',   // perdre du poids
        '2' => 'weight_gain',   // prise de masse (bulk)
        '3' => 'maintain',      // maintien
        '4' => 'gain_muscles',  // prise de muscle "lean"
        '5' => 'recomp',  // recomposition (perte de gras + gain muscle)
    ];

    private const array ACTIVITY_PAL = [
        '1' => 1.10, // bedrest
        '2' => 1.20, // sedentary
        '3' => 1.35, // light_walk
        '4' => 1.45, // light_sport
        '5' => 1.55, // moderate
        '6' => 1.60, // moderate_sport
        '7' => 1.70, // active
        '8' => 1.85, // very_active
    ];

    private const STEPS_BY_ACTIVITY = [
        '1' => 1500,
        '2' => 3000,
        '3' => 6000,
        '4' => 8000,
        '5' => 9000,
        '6' => 10000,
        '7' => 12000,
        '8' => 14000,
    ];

    /** Distribution des repas en fonction du nombre de repas (somme = 1.0) */
    private const MEAL_DISTS = [
        2 => [0.45, 0.55],                                // matin / soir
        3 => [0.30, 0.40, 0.30],                          // ptit dej / dej / dîner
        4 => [0.25, 0.35, 0.25, 0.15],                    // + collation
        5 => [0.22, 0.30, 0.10, 0.28, 0.10],              // 2 collations
        6 => [0.20, 0.25, 0.10, 0.25, 0.10, 0.10],        // 3 collations
    ];

    private const BASE_PROFILES = [
        'weight_loss' => [
            'breakfast'       => 0.20,
            'snack_morning'   => 0.10,
            'lunch'           => 0.35,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.25,
        ],
        'gain_muscles' => [
            'breakfast'       => 0.25,
            'snack_morning'   => 0.10,
            'lunch'           => 0.35,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.20,
        ],
        'weight_gain' => [
            'breakfast'       => 0.25,
            'snack_morning'   => 0.10,
            'lunch'           => 0.40,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.15,
        ],
        'maintain' => [
            'breakfast'       => 0.22,
            'snack_morning'   => 0.10,
            'lunch'           => 0.33,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.25,
        ],
    ];

    public function build(CreateProgramData $d): array
    {
        // 0) Préparation / âge
        $age = Carbon::now()->diffInYears($d->birthDate);

        // 1) BMR (Mifflin-St Jeor) – poids(kg), taille(cm), âge(ans)
        $bmr = match ($d->gender) {
            'male'   => 10 * $d->startWeight + 6.25 * $d->height - 5 * $age + 5,
            'female' => 10 * $d->startWeight + 6.25 * $d->height - 5 * $age - 161,
            default  => ( (10*$d->startWeight + 6.25*$d->height - 5*$age + 5)
                    + (10*$d->startWeight + 6.25*$d->height - 5*$age - 161) ) / 2,
        };

        // 2) TDEE via PAL
        $pal  = self::ACTIVITY_PAL[$d->activityLevelId] ?? 1.2;
        $tdee = $bmr * $pal;

        // 3) Route selon le goal
        $goalCode = self::GOAL_MAP[$d->goalId] ?? 'maintain';

        return match ($goalCode) {
            'weight_loss'  => $this->planForWeightLoss($d, $tdee, $bmr, $pal),
            'gain_muscles' => $this->planForMuscleGain($d, $tdee, $bmr, $pal),
            'weight_gain'  => $this->planForWeightGain($d, $tdee, $bmr, $pal),
            'recomp'       => $this->planForRecomp($d, $tdee, $bmr, $pal),
            default        => $this->planForMaintain($d, $tdee, $bmr, $pal),
        };
    }

    /**
     * Répartition par créneaux réels choisis par l'utilisateur.
     * $mealUse peut contenir: breakfast, snack_morning, lunch, snack_afternoon, dinner (doublons permis)
     * L'ordre de $mealUse détermine l'ordre des sorties (meal_1, meal_2, ...)
     */
    private function distributeByMealUse(int $dailyKcal, array $mealUse, array $baseProfile): array
    {
        // Map codes -> canonical slot names
        $mealMap = [
            1 => 'breakfast',
            2 => 'snack_morning',
            3 => 'lunch',
            4 => 'snack_afternoon',
            5 => 'dinner',
        ];

        if (empty($mealUse)) {
            // Fallback: un seul bloc, clé explicite
            return ['other' => (int) $dailyKcal];
        }

        // Normaliser $mealUse en noms de slots (accepte codes numériques ou noms directs)
        $slots = [];
        foreach ($mealUse as $v) {
            if (is_int($v) || (is_string($v) && ctype_digit($v))) {
                $code = (int) $v;
                if (isset($mealMap[$code])) {
                    $slots[] = $mealMap[$code];
                }
            } elseif (is_string($v)) {
                $slots[] = $v; // ex: 'lunch'
            }
        }

        if (empty($slots)) {
            return ['other' => (int) $dailyKcal];
        }

        // Comptage des occurrences par slot (pour partager la part d'un slot répété)
        $occ = array_count_values($slots);

        // Répartition brute par slot (accumulation)
        $rawBySlot = [];
        foreach ($slots as $slot) {
            $slotShare = $baseProfile[$slot] ?? 0.0;
            $count     = max(1, $occ[$slot] ?? 1);
            // chaque occurrence du même slot reçoit une fraction égale de sa part totale
            $rawBySlot[$slot] = ($rawBySlot[$slot] ?? 0.0) + ($slotShare / $count);
        }

        // Si aucun des slots demandés n'existe dans le profil -> parts égales sur les slots demandés
        $sum = array_sum($rawBySlot);
        if ($sum <= 0) {
            $equal = 1 / max(1, count($slots));
            $rawBySlot = [];
            foreach ($slots as $slot) {
                $rawBySlot[$slot] = ($rawBySlot[$slot] ?? 0) + $equal / max(1, ($occ[$slot] ?? 1));
            }
            $sum = array_sum($rawBySlot);
        }

        // Normalisation (sécurité)
        if ($sum > 0 && abs($sum - 1.0) > 1e-9) {
            foreach ($rawBySlot as $k => $v) {
                $rawBySlot[$k] = $v / $sum;
            }
        }

        // Conversion en kcal, clés = noms de slots (breakfast, lunch, ...)
        $out = [];
        foreach ($rawBySlot as $slot => $ratio) {
            $out[$slot] = (int) round($dailyKcal * $ratio);
        }

        return $out;
    }

    /** Common: compute macro ratios in % from kcal */
    private function macroRatios(int $dailyKcal, int $proteinKc, int $fatKc): array
    {
        $proteinPct = (int) round(($proteinKc / max(1, $dailyKcal)) * 100);
        $fatPct     = (int) round(($fatKc     / max(1, $dailyKcal)) * 100);
        $carbPct    = 100 - $proteinPct - $fatPct;
        return [$proteinPct, $fatPct, $carbPct];
    }

    /** Common: map steps, with optional floor for weight loss */
    private function stepsFor(string $activityLevelId, bool $enCut = false): int
    {
        $steps = self::STEPS_BY_ACTIVITY[$activityLevelId] ?? 2000;
        if ($enCut) {
            $steps = max($steps, 3000);
        }
        return $steps;
    }

    /** Common: water (ml), weight may be target or start */
    private function waterMlFor(float $weightRefKg, string $activityLevelId, ?string $gender = null): int
    {
        $factor = $gender === 'female' ? 0.031 : 0.035;
        $baseWaterL = $weightRefKg * $factor;

        $bonusL = 0.0;
        if (in_array($activityLevelId, ['7','8'], true)) {
            $bonusL += 0.5;
        }


        $waterMl = ($baseWaterL + $bonusL) * 1000;

        // Arrondi au verre de 250 ml
        return max(2000, (int) (round($waterMl / 250) * 250));
    }

    /** Goal 1: Weight loss */
    private function planForWeightLoss(CreateProgramData $d, float $tdee, float $bmr, float $pal): array
    {
        // Déficit depuis hebdoLoss (cap 0.7%/sem)
        $goalAdjust = -450; // fallback
        if ($d->hebdoLoss > 0) {
            $maxWeeklyKg    = round($d->startWeight * 0.009, 3);
            $weeklyKg       = min($d->hebdoLoss, $maxWeeklyKg);
            $dailyFromHebdo = - ($weeklyKg * 7700) / 7.0; // kcal/j
            $goalAdjust     = (int) round($dailyFromHebdo);
        }
        // kcal/j avec cap déficit 25% du TDEE
        $dailyKcal = (int) round($tdee + $goalAdjust);
        $minByDeficit = (int) round($tdee * 0.75);
        $dailyKcal = max($dailyKcal, $minByDeficit);
        // Plancher calorique différencié par sexe
        $minKcal = $d->gender === 'male' ? 1700 : 1400;
        $dailyKcal = max($dailyKcal, $minKcal);

        // Protéines sur poids cible (1.8–2.2 g/kg → 2.0 par défaut)
        $useWeightForProtein = ($d->targetWeight > 0 && $d->targetWeight < $d->startWeight) ? $d->targetWeight : $d->startWeight;
        $proteinPerKg = 2.0; // clampé ensuite si besoin
        $proteinPerKg = max(1.8, min(2.2, $proteinPerKg));
        $proteinG  = (int) round($useWeightForProtein * $proteinPerKg);
        $proteinKc = $proteinG * 4;

        // Lipides 25% kcal par défaut (plancher 0.8g/kg ou 50g, le plus élevé)
        $fatKc = (int) round($dailyKcal * 0.25);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($useWeightForProtein * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // Glucides = reste (plancher 100 g)
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);
        $minCarbG  = 100; $minCarbKc = $minCarbG * 4;
        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // réduire lipides jusqu'à 20% kcal ou minFatG mini
            $fatFloorKc = max((int) round($dailyKcal * 0.20), $minFatG * 9);
            if ($fatKc > $fatFloorKc) { $take = min($needed, $fatKc - $fatFloorKc); $fatKc -= $take; $needed -= $take; }
            // réduire prot jusqu'à 1.8 g/kg ref si besoin
            if ($needed > 0) {
                $proteinFloorG  = (int) round($useWeightForProtein * 1.8);
                $proteinFloorKc = $proteinFloorG * 4;
                if ($proteinKc > $proteinFloorKc) { $take = min($needed, $proteinKc - $proteinFloorKc); $proteinKc -= $take; $needed -= $take; }
            }
            $fatG     = (int) round($fatKc / 9);
            $proteinG = (int) round($proteinKc / 4);
            $carbKc   = max(0, $dailyKcal - ($proteinKc + $fatKc));
            $carbG    = (int) round($carbKc / 4);
        }

        // Ajustement glucides si sportif (niveau id >= 6)
        if ((int)$d->activityLevelId >= 6) {
            $minCarbG = (int) round($useWeightForProtein * 3.0);
            if ($carbG < $minCarbG) {
                $needed = $minCarbG*4 - $carbKc;
                $fatFloorKc = max((int) round($dailyKcal * 0.20), $minFatG * 9);
                if ($fatKc > $fatFloorKc) {
                    $take = min($needed, $fatKc - $fatFloorKc);
                    $fatKc -= $take; $carbKc += $take;
                    $fatG = (int) round($fatKc / 9); $carbG = (int) round($carbKc / 4);
                }
            }
        }

        // Rebalance: prot% >= carbs% (cap 2.4 g/kg sur prot)
        $proteinPctTmp = ($proteinKc / max(1, $dailyKcal)) * 100;
        $carbPctTmp    = ($carbKc    / max(1, $dailyKcal)) * 100;
        if ($carbPctTmp > $proteinPctTmp) {
            $maxProtPerKg = 2.4;
            $targetCarbKc = min($carbKc, $proteinKc);
            $shift        = $carbKc - $targetCarbKc;
            if ($shift > 0) {
                $proteinKcNew = $proteinKc + $shift;
                $maxProteinKc = (int) round($maxProtPerKg * $useWeightForProtein) * 4;
                if ($proteinKcNew > $maxProteinKc) {
                    $excess       = $proteinKcNew - $maxProteinKc;
                    $proteinKcNew = $maxProteinKc;
                    $targetCarbKc += $excess;
                }
                $proteinKc = $proteinKcNew; $carbKc = $targetCarbKc;
                $proteinG  = (int) round($proteinKc / 4);
                $carbG     = (int) round($carbKc / 4);
            }
        }

        // ---- LISSAGE MACRO COMPLET ----
        // Objectif: éviter prot/lip trop hauts au détriment des glucides,
        // respecter des bornes diététiques et l'adhérence long-terme.
        $minCarbGBase = 100;
        $minCarbGPal  = ((int)$d->activityLevelId >= 6)
            ? max($minCarbGBase, (int) round($useWeightForProtein * 3.0))   // sportif régulier
            : max($minCarbGBase, (int) round($useWeightForProtein * 1.5));   // sédentaire / léger
        $minCarbKc    = $minCarbGPal * 4;

        // 1) Plafond % protéines raisonnable (ex. 32%)
        $protPctHardCap = 32; // ajustable (30–34)
        $currProtPct    = ($proteinKc / max(1, $dailyKcal)) * 100;
        $protFloorKc    = (int) round($useWeightForProtein * 1.8) * 4; // on ne descend jamais sous 1.8 g/kg
        if ($currProtPct > $protPctHardCap) {
            $targetProtKc = (int) round($dailyKcal * $protPctHardCap / 100);
            $proteinKc    = max($targetProtKc, $protFloorKc);
        }

        // 2) Lipides entre 20–30% si possible (sans passer sous 0.8 g/kg)
        $lipMinPct  = 20;
        $lipMaxPct  = 30;
        $lipMinKc   = max((int) round($dailyKcal * $lipMinPct / 100), (int) round($useWeightForProtein * 0.8) * 9);
        $lipMaxKc   = (int) round($dailyKcal * $lipMaxPct / 100);
        $fatKc      = max($lipMinKc, min($fatKc, $lipMaxKc));

        // 3) Recalcul des glucides avec plancher dynamique
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;

            // a) réduire lipides jusqu'à lipMinKc
            if ($fatKc > $lipMinKc) {
                $take = min($needed, $fatKc - $lipMinKc);
                if ($take > 0) {
                    $fatKc  -= $take;
                    $carbKc += $take;
                    $needed -= $take;
                }
            }

            // b) réduire protéines jusqu'à 1.8 g/kg si encore nécessaire
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc);
                if ($take > 0) {
                    $proteinKc -= $take;
                    $carbKc    += $take;
                    $needed    -= $take;
                }
            }
        }

        // 4) Convertir en grammes (final)
        $proteinG = (int) round($proteinKc / 4);
        $fatG     = (int) round($fatKc / 9);
        $carbG    = (int) round($carbKc / 4);

        // Ratios
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // Steps & water (poids cible si dispo)
        $steps   = $this->stepsFor($d->activityLevelId, true);
        $wWeight = ($d->targetWeight > 0 && $d->targetWeight < $d->startWeight) ? $d->targetWeight : $d->startWeight;
        $waterMl = $this->waterMlFor($wWeight, $d->activityLevelId);

        // Répartition par repas
        $profile = self::BASE_PROFILES['weight_loss'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        // Durée
        $durationDays = null;
        if ($d->targetWeight > 0) {
            $deltaKg    = $d->targetWeight - $d->startWeight; // négatif si perte
            $weeklyRate = $d->hebdoLoss > 0 ? $d->hebdoLoss : 0.5;
            if ($weeklyRate > 0) { $durationDays = (int) round(abs($deltaKg) / $weeklyRate * 7); }
        }

        return [
            'daily_kcal' => $dailyKcal,
            'macronutrients' => [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            'macro_ratios' => ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            'daily_steps_goal' => $steps,
            'daily_water_goal_ml' => $waterMl,
            'per_meal_kcal' => $perMeal,
            'duration_days' => $durationDays,
            'meta' => [ 'bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_adjust_kcal' => $goalAdjust, 'goal_code' => 'weight_loss' ],
        ];
    }

    /** Goal 4: Gain muscles */
    private function planForMuscleGain(CreateProgramData $d, float $tdee, float $bmr, float $pal): array
    {
        // Surplus modéré 10–15% du TDEE (lean gain)
        $dailyKcal = (int) round($tdee * 1.12); // 12% par défaut

        // Protéines 1.6–2.0 g/kg (point de départ 1.8 g/kg)
        $proteinPerKg = 1.8;
        $proteinG  = (int) round($d->startWeight * $proteinPerKg);
        $proteinKc = $proteinG * 4;
        $protFloorKc = (int) round($d->startWeight * 1.6) * 4; // on ne descend pas sous 1.6 g/kg
        $protCeilKc  = (int) round($d->startWeight * 2.2) * 4; // max utile 2.2 g/kg
        $proteinKc   = max($protFloorKc, min($proteinKc, $protCeilKc));

        // Lipides 25% kcal (min 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.25);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($d->startWeight * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // Carbs init = reste
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        // Cibles glucidiques pour performance / hypertrophie
        $targetCarbG = (int) round($d->startWeight * 4.5); // cible (4–6 g/kg), on vise 4.5
        $minCarbG    = (int) round($d->startWeight * (((int)$d->activityLevelId >= 6) ? 3.5 : 3.0)); // plancher dynamique
        $minCarbKc   = $minCarbG * 4;

        // ---- LISSAGE MACRO (lean gain) ----
        // 1) Assurer lipides 20–30% et >= 0.8 g/kg
        $lipMinPct = 20; $lipMaxPct = 30;
        $lipMinKc  = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
        $lipMaxKc  = (int) round($dailyKcal * $lipMaxPct / 100);
        $fatKc     = max($lipMinKc, min($fatKc, $lipMaxKc));

        // 2) Protéines cap à ~30% kcal (sans passer sous 1.6 g/kg)
        $protPctCap = 30;
        $currProtPct = ($proteinKc / max(1, $dailyKcal)) * 100;
        if ($currProtPct > $protPctCap) {
            $targetProtKc = (int) round($dailyKcal * $protPctCap / 100);
            $proteinKc    = max($targetProtKc, $protFloorKc);
        }

        // 3) Recalcul des glucides, avec plancher minCarb et tentative vers targetCarb
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // a) réduire lipides jusqu'à lipMinKc
            if ($fatKc > $lipMinKc) {
                $take = min($needed, $fatKc - $lipMinKc);
                if ($take > 0) { $fatKc -= $take; $carbKc += $take; $needed -= $take; }
            }
            // b) réduire protéines jusqu'à 1.6 g/kg si encore nécessaire
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc);
                if ($take > 0) { $proteinKc -= $take; $carbKc += $take; $needed -= $take; }
            }
        } else {
            // Si on a de la marge au-dessus du plancher, tenter d'approcher targetCarbG
            $targetKc = $targetCarbG * 4;
            if ($carbKc < $targetKc) {
                $needed = $targetKc - $carbKc;
                // On peut libérer un peu depuis les lipides (jusqu'à 22% si besoin)
                $softLipFloorKc = max((int) round($dailyKcal * 0.22), $lipMinKc); // ne pas descendre sous lipMinKc
                if ($fatKc > $softLipFloorKc) {
                    $take = min($needed, $fatKc - $softLipFloorKc);
                    if ($take > 0) { $fatKc -= $take; $carbKc += $take; }
                }
            }
        }

        // 4) Convertir en grammes (final)
        $proteinG = (int) round($proteinKc / 4);
        $fatG     = (int) round($fatKc / 9);
        $carbG    = (int) round($carbKc / 4);

        // Ratios finaux
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // Steps & water
        $steps   = self::STEPS_BY_ACTIVITY[$d->activityLevelId] ?? 8000;
        $waterMl = $this->waterMlFor($d->startWeight, $d->activityLevelId, $d->gender ?? null);
        $profile = self::BASE_PROFILES['gain_muscles'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        return [
            'daily_kcal' => $dailyKcal,
            'macronutrients' => [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            'macro_ratios' => ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            'daily_steps_goal' => $steps,
            'daily_water_goal_ml' => $waterMl,
            'per_meal_kcal' => $perMeal,
            'duration_days' => null,
            'meta' => [ 'bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_adjust_kcal' => (int) round($dailyKcal - $tdee), 'goal_code' => 'gain_muscles' ],
        ];
    }

    /** Goal 2: Weight gain (bulk) */
    private function planForWeightGain(CreateProgramData $d, float $tdee, float $bmr, float $pal): array
    {
        // Surplus modéré ~15% (bulk contrôlé)
        $dailyKcal = (int) round($tdee * 1.15);

        // Protéines 1.8 g/kg (bornes 1.6–2.2 g/kg)
        $proteinPerKg = 1.8;
        $proteinG  = (int) round($d->startWeight * $proteinPerKg);
        $proteinKc = $proteinG * 4;
        $protFloorKc = (int) round($d->startWeight * 1.6) * 4;
        $protCeilKc  = (int) round($d->startWeight * 2.2) * 4;
        $proteinKc   = max($protFloorKc, min($proteinKc, $protCeilKc));

        // Lipides 25% kcal avec plancher individuel (>= 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.25);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($d->startWeight * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // Carbs init = reste
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        // Cibles glucidiques pour la prise de masse saine
        $targetCarbG = (int) round($d->startWeight * 5.0);  // cible (4.5–6 g/kg), on vise 5.0
        $minCarbG    = (int) round($d->startWeight * 3.0);  // plancher absolu
        if ((int)$d->activityLevelId >= 6) {
            $minCarbG = max($minCarbG, (int) round($d->startWeight * 3.5));
        }
        $minCarbKc = $minCarbG * 4;

        // ---- LISSAGE MACRO (bulk) ----
        // 1) Bornes lipides 22–32% et >= 0.8 g/kg
        $lipMinPct = 22; $lipMaxPct = 32;
        $lipMinKc  = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
        $lipMaxKc  = (int) round($dailyKcal * $lipMaxPct / 100);
        $fatKc     = max($lipMinKc, min($fatKc, $lipMaxKc));

        // 2) Protéines capées à ~28% des kcal (sans passer sous 1.6 g/kg)
        $protPctCap  = 28;
        $currProtPct = ($proteinKc / max(1, $dailyKcal)) * 100;
        if ($currProtPct > $protPctCap) {
            $targetProtKc = (int) round($dailyKcal * $protPctCap / 100);
            $proteinKc    = max($targetProtKc, $protFloorKc);
        }

        // 3) Recalcul des glucides avec plancher dynamique et tentative vers cible
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // a) réduire lipides jusqu'à lipMinKc
            if ($fatKc > $lipMinKc) {
                $take = min($needed, $fatKc - $lipMinKc);
                if ($take > 0) { $fatKc -= $take; $carbKc += $take; $needed -= $take; }
            }
            // b) réduire protéines jusqu'à 1.6 g/kg si encore nécessaire
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc);
                if ($take > 0) { $proteinKc -= $take; $carbKc += $take; $needed -= $take; }
            }
        } else {
            // Si marge, rapprocher de la cible carb (5 g/kg) en libérant un peu de lipides (jusqu'à 24%)
            $targetKc = $targetCarbG * 4;
            if ($carbKc < $targetKc) {
                $needed = $targetKc - $carbKc;
                $softLipFloorKc = max((int) round($dailyKcal * 0.24), $lipMinKc);
                if ($fatKc > $softLipFloorKc) {
                    $take = min($needed, $fatKc - $softLipFloorKc);
                    if ($take > 0) { $fatKc -= $take; $carbKc += $take; }
                }
            }
        }

        // 4) Convertir en grammes (final)
        $proteinG = (int) round($proteinKc / 4);
        $fatG     = (int) round($fatKc / 9);
        $carbG    = (int) round($carbKc / 4);

        // Ratios finaux
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // Steps & water (pas de plancher cut ici)
        $steps   = self::STEPS_BY_ACTIVITY[$d->activityLevelId] ?? 8000;
        $waterMl = $this->waterMlFor($d->startWeight, $d->activityLevelId, $d->gender ?? null);
        $profile = self::BASE_PROFILES['weight_gain'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        // Durée (si targetWeight > startWeight)
        $durationDays = null;
        if ($d->targetWeight > 0 && $d->targetWeight > $d->startWeight) {
            $deltaKg    = $d->targetWeight - $d->startWeight;
            $weeklyRate = 0.25; // kg/sem
            $durationDays = (int) round(abs($deltaKg) / $weeklyRate * 7);
        }

        return [
            'daily_kcal' => $dailyKcal,
            'macronutrients' => [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            'macro_ratios' => ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            'daily_steps_goal' => $steps,
            'daily_water_goal_ml' => $waterMl,
            'per_meal_kcal' => $perMeal,
            'duration_days' => $durationDays,
            'meta' => [ 'bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_adjust_kcal' => (int) round($dailyKcal - $tdee), 'goal_code' => 'weight_gain' ],
        ];
    }

    /** Goal 3: Maintain */
    private function planForMaintain(CreateProgramData $d, float $tdee, float $bmr, float $pal): array
    {
        $dailyKcal = (int) round($tdee);

        // Protéines ~1.8 g/kg avec bornes (1.6–2.2 g/kg)
        $proteinPerKg = 1.8;
        $proteinG  = (int) round($d->startWeight * $proteinPerKg);
        $proteinKc = $proteinG * 4;
        $protFloorKc = (int) round($d->startWeight * 1.6) * 4; // plancher 1.6 g/kg
        $protCeilKc  = (int) round($d->startWeight * 2.2) * 4; // plafond 2.2 g/kg
        $proteinKc   = max($protFloorKc, min($proteinKc, $protCeilKc));

        // Lipides 25% kcal avec plancher individuel (>= 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.25);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($d->startWeight * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // Carbs init = reste
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        // ---- LISSAGE MACRO (maintien) ----
        // 1) Protéines capées à ~30% des kcal (sans passer sous 1.6 g/kg)
        $protPctCap  = 30;
        $currProtPct = ($proteinKc / max(1, $dailyKcal)) * 100;
        if ($currProtPct > $protPctCap) {
            $targetProtKc = (int) round($dailyKcal * $protPctCap / 100);
            $proteinKc    = max($targetProtKc, $protFloorKc);
        }

        // 2) Lipides bornés 22–35% et >= 0.8 g/kg
        $lipMinPct = 22; $lipMaxPct = 35;
        $lipMinKc  = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
        $lipMaxKc  = (int) round($dailyKcal * $lipMaxPct / 100);
        $fatKc     = max($lipMinKc, min($fatKc, $lipMaxKc));

        // 3) Recalcul des glucides avec plancher dynamique par activité
        // Plancher absolu 130 g; sédentaire 2.0 g/kg; modéré 2.5 g/kg; élevé (>=6) 3.0 g/kg
        $absMinCarbG = 130;
        $activityId  = (int) $d->activityLevelId;
        $kgBasedMin  = $d->startWeight * ($activityId >= 6 ? 3.0 : ($activityId >= 5 ? 2.5 : 2.0));
        $minCarbG    = (int) round(max($absMinCarbG, $kgBasedMin));
        $minCarbKc   = $minCarbG * 4;

        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // a) réduire lipides jusqu'à lipMinKc
            if ($fatKc > $lipMinKc) {
                $take = min($needed, $fatKc - $lipMinKc);
                if ($take > 0) { $fatKc -= $take; $carbKc += $take; $needed -= $take; }
            }
            // b) réduire protéines jusqu'à 1.6 g/kg si nécessaire
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc);
                if ($take > 0) { $proteinKc -= $take; $carbKc += $take; $needed -= $take; }
            }
        }

        // 4) Convertir en grammes (final)
        $proteinG = (int) round($proteinKc / 4);
        $fatG     = (int) round($fatKc / 9);
        $carbG    = (int) round($carbKc / 4);

        // Ratios finaux
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // Steps & water (maintien : pas de plancher cut)
        $steps   = self::STEPS_BY_ACTIVITY[$d->activityLevelId] ?? 8000;
        $waterMl = $this->waterMlFor($d->startWeight, $d->activityLevelId, $d->gender ?? null);
        $profile = self::BASE_PROFILES['maintain'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        return [
            'daily_kcal' => $dailyKcal,
            'macronutrients' => [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            'macro_ratios' => ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            'daily_steps_goal' => $steps,
            'daily_water_goal_ml' => $waterMl,
            'per_meal_kcal' => $perMeal,
            'duration_days' => null,
            'meta' => [ 'bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_adjust_kcal' => 0, 'goal_code' => 'maintain' ],
        ];
    }

    private function planForRecomp(CreateProgramData $d, float $tdee, float $bmr, float $pal): array
    {
        // 1) Cible kcal (simple) : IMC proxy
        $bmi = $d->startWeight / pow($d->height/100, 2);
        $dailyKcal = (int) round($tdee * ($bmi >= 27 ? 0.90 : 1.00)); // -10% ou maintenance

        // 2) Si trainingDaysPerWeek fourni, calorie cycling
        $trainDays = max(0, min(($d->trainingDaysPerWeek ?? 0), 6));
        if ($trainDays > 0) {
            $restDays  = 7 - $trainDays;
            $trainKcal = (int) round($tdee * 1.05);
            $restKcal  = (int) round($tdee * 0.85);
            // moyenne hebdo pour affichage général
            $weeklyAvg = (int) round( ($trainKcal*$trainDays + $restKcal*$restDays)/7 );
            $dailyKcal = $weeklyAvg;
        }

        // 3) Protéines 2.0–2.2 g/kg (cible si dispo)
        $wRef = ($d->targetWeight > 0 && $d->targetWeight < $d->startWeight) ? $d->targetWeight : $d->startWeight;
        $protPerKg = 2.1; // centre de la fourchette
        $protFloor = 2.0; $protCeil = 2.4;
        $protPerKg = max($protFloor, min($protCeil, $protPerKg));
        $proteinG  = (int) round($wRef * $protPerKg);
        $proteinKc = $proteinG * 4;

        // 4) Lipides 25–30% (≥ 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.27);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($wRef * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // 5) Glucides = reste avec plancher selon activité
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        $act = (int) $d->activityLevelId;
        $minCarbPerKg = $act >= 6 ? 3.0 : ($act >= 5 ? 2.5 : 1.5);
        $minCarbG = max(130, (int) round($wRef * $minCarbPerKg));
        $minCarbKc = $minCarbG * 4;

        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // réduire lipides vers 25% (plancher ≥ 0.8 g/kg)
            $lipMinPct = 25; $lipMinKc = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
            if ($fatKc > $lipMinKc) { $take = min($needed, $fatKc - $lipMinKc); $fatKc -= $take; $needed -= $take; }
            // réduire prot jusqu’à 2.0 g/kg si besoin
            $protFloorKc = (int) round($wRef * 2.0) * 4;
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc); $proteinKc -= $take; $needed -= $take;
            }
            $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
            $carbG  = (int) round($carbKc / 4);
            $fatG   = (int) round($fatKc / 9);
            $proteinG = (int) round($proteinKc / 4);
        }

        // 6) Ratios
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // 7) Steps & water
        $steps   = $this->stepsFor($d->activityLevelId, true); // plancher 7k
        $waterMl = $this->waterMlFor($wRef, $d->activityLevelId, $d->gender ?? null);

        // 8) Répartition repas (met une part autour de l’entraînement si tu as l’info)
        $profile = self::BASE_PROFILES['gain_muscles']; // glucides un peu plus hauts midi/soir
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        return [
            'daily_kcal' => $dailyKcal,
            'macronutrients' => [
                'protein' => ['g' => $proteinG, 'kcal' => $proteinKc],
                'fat'     => ['g' => $fatG,     'kcal' => $fatKc],
                'carbs'   => ['g' => $carbG,    'kcal' => $carbKc],
            ],
            'macro_ratios' => ['protein' => (int)$proteinPct, 'fat' => (int)$fatPct, 'carbs' => (int)$carbPct],
            'daily_steps_goal' => $steps,
            'daily_water_goal_ml' => $waterMl,
            'per_meal_kcal' => $perMeal,
            'duration_days' => null,
            'meta' => ['bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_code' => 'recomp'],
        ];
    }
}
