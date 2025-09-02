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
        'recomp' => [
            'breakfast'       => 0.23,
            'snack_morning'   => 0.10,
            'lunch'           => 0.34,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.23,
        ],
    ];

    /**
     * Get the base meal profile for a specific goal.
     *
     * @param string $goal
     * @return array|null
     */
    public function getBaseProfile(string $goal): ?array
    {
        return self::BASE_PROFILES[$goal] ?? null;
    }


    public function build(CreateProgramData $d): \App\Features\ProgramPlanner\Domain\ProgramPlan
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
    public function distributeByMealUse(int $dailyKcal, array $mealUse, array $baseProfile): array
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
        return array_map(function ($ratio) use ($dailyKcal) {
            return (int)round($dailyKcal * $ratio);
        }, $rawBySlot);
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

        $act = (int) $activityLevelId; // normalisation pour les comparaisons
        $bonusL = 0.0;
        if ($act >= 7) {
            $bonusL += 0.5;
        }

        $waterMl = ($baseWaterL + $bonusL) * 1000;
        return max(2000, (int) (round($waterMl / 250) * 250));
    }

    /**
     * Calcule un cap adaptatif du déficit (en % du TDEE) selon IMC, activité et progression observée.
     * - IMC ≥ 35  → 28–33%
     * - IMC 30–35 → 26–31%
     * - IMC < 30  → 22–28%
     * Ajuste légèrement à la baisse chez les très actifs (éviter d’étrangler la perf),
     * et peut raffiner selon une perte observée (weeklyObservedPct) pour viser 0.5–1.0%/sem.
     */
    private function deficitCapPct(float $bmi, int $activityLevelId, ?float $weeklyObservedPct = null, bool $isRecomp = false): float
    {
        // 1) Base par IMC (plus permissif par défaut)
        if ($bmi >= 35.0) {
            $min = 0.28; $max = 0.33;   // autorise jusqu'à ~33% de TDEE
        } elseif ($bmi >= 30.0) {
            $min = 0.26; $max = 0.31;   // autorise jusqu'à ~31% de TDEE
        } else {
            $min = 0.22; $max = 0.28;   // autorise jusqu'à ~28% de TDEE
        }

        // 2) Ajustement par activité: très actifs (>=6) → pénalité plus douce
        if ($activityLevelId >= 6) {
            $max -= 0.01; // pénalité plus douce
            $min = max(0.18, $min - 0.00);
        }

        // 3) Option: progression observée (viser 0.5–1.0%/sem)
        if ($weeklyObservedPct !== null && $weeklyObservedPct > 0) {
            // si >1.0% → trop agressif: resserrer cap vers le bas
            if ($weeklyObservedPct > 0.010) {
                $max = max($min, $max - 0.02);
            }
            // si <0.5% → trop lent: on peut ouvrir légèrement le cap
            elseif ($weeklyObservedPct < 0.005) {
                $max = min($max + 0.02, 0.32); // limite dure 32%
            }
        }

        // 4) Permettre override via config si présent
        $cfgMin = (float) (config('program.deficit_cap_min', $min));
        $cfgMax = (float) (config('program.deficit_cap_max', $max));

        // Recomp: rendre le cap un peu plus conservateur (par défaut -2 points)
        $cap = max($cfgMin, $cfgMax);
        if ($isRecomp) {
            $tweak = (float) config('program.recomp_cap_tweak_deficit', -0.02);
            $cap += $tweak;                    // tweak négatif ⇒ cap plus strict
            $cap = max(0.10, $cap);            // garde-fou bas
        }
        // Hard clamp de sécurité (mais permet jusqu'à ~35%)
        $cap = min($cap, (float) config('program.deficit_cap_hard_ceiling', 0.35));
        return $cap;
    }

    /**
     * Calcule le déficit quotidien (kcal/j) à partir de hebdoLoss (kg/sem),
     * avec bornes de sécurité (≤ 1.0% du poids/sem et ≤ cap adaptatif du TDEE).
     * Retourne aussi des méta utiles pour le debug.
     */
    private function dailyDeficitFromHebdoLoss(CreateProgramData $d, float $tdee, bool $isRecomp = false): array
    {
        // 1) Cible demandée (hebdoLoss) et cap par % du poids/sem (1.0%)
        $requestedWeeklyKg = ($d->hebdoChange ?? 0) > 0 ? (float) $d->hebdoChange : 0.5;
        $weeklyKgCapByWeight = $d->startWeight > 0 ? round($d->startWeight * 0.010, 3) : $requestedWeeklyKg;
        $weeklyKgByWeight    = min($requestedWeeklyKg, $weeklyKgCapByWeight);

        $cappedBy = null;
        if ($requestedWeeklyKg > $weeklyKgByWeight) {
            $cappedBy = 'weekly_pct_cap';
        }

        // 2) Cap global par % du TDEE (adaptatif)
        $bmi   = ($d->height > 0) ? ($d->startWeight / pow($d->height/100, 2)) : 25.0;
        $capPct = $this->deficitCapPct($bmi, (int) $d->activityLevelId, null, $isRecomp);
        $maxDailyDeficitByPct = - ($tdee * $capPct); // kcal/j (négatif)

        // 3) Déficit quotidien issu de la demande (après cap "poids/sem")
        $dailyFromRequestedByWeight = - ($weeklyKgByWeight * 7700.0) / 7.0; // kcal/j

        // 4) Déficit effectif appliqué = borné par le cap TDEE
        $adjust = (int) round(max($dailyFromRequestedByWeight, $maxDailyDeficitByPct));
        if ($dailyFromRequestedByWeight < $maxDailyDeficitByPct) {
            $cappedBy = 'tdee_cap';
        }

        // 5) Perte hebdo EFFECTIVE (dérivée du déficit réellement appliqué)
        $effectiveWeeklyKg  = round(abs(($adjust * 7) / 7700.0), 3);
        $effectiveWeeklyPct = $d->startWeight > 0 ? round($effectiveWeeklyKg / $d->startWeight, 4) : 0.0;

        // 6) Maximum théorique atteignable cette semaine (par %TDEE UNIQUEMENT)
        $maxWeeklyKgByTdee = round(abs(($maxDailyDeficitByPct * 7) / 7700.0), 3);

        return [
            // kcal/j négatif (à AJOUTER au TDEE pour obtenir la cible journalière)
            'adjust'              => $adjust,

            // Perte HEBDO EFFECTIVE (post-cap), toujours cohérente avec "adjust"
            'weekly_kg'           => $effectiveWeeklyKg,
            'weekly_pct'          => $effectiveWeeklyPct,

            // Optionnel: pour transparence, on expose la demande initiale
            'requested_weekly_kg' => $requestedWeeklyKg,

            // Debug / méta cap
            'capped_by'           => $cappedBy,
            'cap_pct'             => $capPct,

            // Plafond atteignable cette semaine (par %TDEE uniquement)
            'max_weekly_kg'       => $maxWeeklyKgByTdee,
        ];
    }

    /**
     * Applique les garde-fous de sécurité sur une cible kcal/j:
     * - Plancher proportionnel au TDEE (configurable)
     * - Plancher absolu selon le sexe (configurable), ou neutre si non renseigné
     */
    private function clampDailyKcalForSafety(int $candidateDailyKcal, float $tdee, ?string $gender): int
    {
        // 1) Plancher proportionnel au TDEE (configurable)
        $minTdeeRatio = (float) config('program.min_tdee_ratio', 0.70); // ex: 0.70–0.75
        $out = max($candidateDailyKcal, (int) round($tdee * $minTdeeRatio));

        // 2) Plancher absolu selon le sexe (configurable) + neutre si non renseigné
        $g = $gender ? strtolower($gender) : null;
        $minMale     = (int) config('program.min_male_kcal', 1700);
        $minFemale   = (int) config('program.min_female_kcal', 1400);
        $minNeutral  = (int) config('program.min_neutral_kcal', 1500); // défaut si sexe inconnu

        $absFloor = match ($g) {
            'male'   => $minMale,
            'female' => $minFemale,
            default  => $minNeutral,
        };

        $out = max($out, $absFloor);

        return $out;
    }

    /**
     * Cap adaptatif du SURPLUS (en % du TDEE) pour la prise de poids.
     * - IMC < 22  → ~15%
     * - 22 ≤ IMC < 27 → ~12%
     * - IMC ≥ 27 → ~10%
     * Activité élevée (>=6) : +1 point de % maximum pour soutenir la perf.
     */
    private function surplusCapPct(float $bmi, int $activityLevelId, bool $isRecomp = false): float
    {
        if ($bmi < 22.0) {
            $cap = 0.15;
        } elseif ($bmi < 27.0) {
            $cap = 0.12;
        } else {
            $cap = 0.10;
        }
        if ($activityLevelId >= 6) {
            $cap = min(0.16, $cap + 0.01);
        }

        // override via config si présent
        $cfg = config('program.surplus_cap_pct');
        if (is_numeric($cfg)) {
            $cap = (float) $cfg;
        }
        // Recomp: cap surplus plus conservateur (par défaut -1 point)
        if ($isRecomp) {
            $tweak = (float) config('program.recomp_cap_tweak_surplus', -0.01);
            $cap += $tweak;                 // tweak négatif ⇒ cap plus strict
            $cap = max(0.05, $cap);         // garde-fou bas
        }
        return $cap;
    }

    /**
     * Calcule le SURPLUS quotidien (kcal/j) à partir d'un objectif hebdomadaire de prise de poids (kg/sem).
     * Utilise un cap adaptatif (% TDEE) pour éviter de prendre trop de gras.
     * Note: par compatibilité, on lit `hebdoLoss` comme "hebdoGain" si le goal est weight_gain.
     */
    private function dailySurplusFromHebdoGain(CreateProgramData $d, float $tdee, bool $isRecomp = false): array
    {
        $weeklyKgTarget = ($d->hebdoChange ?? 0) > 0 ? (float) $d->hebdoChange : 0.25;

        // Cap hebdo par % poids/sem (lean bulk ~0.6%)
        $weeklyKgCap = $d->startWeight > 0 ? round($d->startWeight * 0.006, 3) : $weeklyKgTarget;
        $weeklyKgReq = min($weeklyKgTarget, $weeklyKgCap);
        $cappedBy    = ($weeklyKgTarget > $weeklyKgReq) ? 'weekly_pct_cap' : null;

        // Demande en kcal/j
        $dailyFromHebdo = ($weeklyKgReq * 7700.0) / 7.0; // positif

        // Cap TDEE (adaptatif)
        $bmi   = ($d->height > 0) ? ($d->startWeight / pow($d->height/100, 2)) : 23.0;
        $capPct = $this->surplusCapPct($bmi, (int) $d->activityLevelId, $isRecomp);
        $maxDailyByPct = $tdee * $capPct;

        // Ajustement appliqué (effectif)
        $adjust = (int) round(min($dailyFromHebdo, $maxDailyByPct));
        if ($dailyFromHebdo > $maxDailyByPct) {
            $cappedBy = 'tdee_cap';
        }

        // 🔴 Recalcule EFFECTIF (comme en perte de poids)
        $effectiveWeeklyKg  = round(($adjust * 7) / 7700.0, 3);
        $effectiveWeeklyPct = $d->startWeight > 0 ? round($effectiveWeeklyKg / $d->startWeight, 4) : 0.0;

        // (Optionnel) plafond “théorique” si seul le cap TDEE s’applique (utile debug)
        $maxWeeklyKgByTdee = round(($maxDailyByPct * 7) / 7700.0, 3);

        return [
            'adjust'        => $adjust,
            'weekly_kg'     => $effectiveWeeklyKg,   // ✅ effectif
            'weekly_pct'    => $effectiveWeeklyPct,  // ✅ effectif
            'capped_by'     => $cappedBy,
            'cap_pct'       => $capPct,
            'max_weekly_kg' => $maxWeeklyKgByTdee,   // info
        ];
    }


    /** Goal 1: Weight loss */
    private function planForWeightLoss(CreateProgramData $d, float $tdee, float $bmr, float $pal): \App\Features\ProgramPlanner\Domain\ProgramPlan
    {

        // ---- Déficit piloté par hebdoLoss + sécurité centralisée ----
        $def = $this->dailyDeficitFromHebdoLoss($d, $tdee);
        $goalAdjust = $def['adjust'];


        $dailyKcal = (int) round($tdee + $goalAdjust);
        $dailyKcal = $this->clampDailyKcalForSafety($dailyKcal, $tdee, $d->gender ?? null);
        // Nouveau garde-fou relatif au poids cible : >= 30 kcal/kg
        $weightRef = ($d->targetWeight > 0 && $d->targetWeight < $d->startWeight) ? $d->targetWeight : $d->startWeight;
        $minByWeight = (int) round($weightRef * 30);
        if ($dailyKcal < $minByWeight) {
            $dailyKcal = $minByWeight;
        }

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
            $minCarbG = (int) round($useWeightForProtein * 2.5);
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
        if (config('program.enforce_protein_over_carbs', false)) {
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
        }

        // ---- LISSAGE MACRO COMPLET ----
        // Objectif: éviter prot/lip trop hauts au détriment des glucides,
        // respecter des bornes diététiques et l'adhérence long-terme.
        $minCarbGBase = 100;
        $minCarbGPal  = ((int)$d->activityLevelId >= 6)
            ? max($minCarbGBase, (int) round($useWeightForProtein * 2.5))   // sportif régulier
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
        $waterMl = $this->waterMlFor($wWeight, $d->activityLevelId, $d->gender);

        // Répartition par repas
        $profile = self::BASE_PROFILES['weight_loss'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        // Durée prend en compte le rythme effectif (weekly_kg)
        $durationDays = null;
        if ($d->targetWeight > 0) {
            $deltaKg    = $d->targetWeight - $d->startWeight; // négatif si perte
            $weeklyRate = $def['weekly_kg'];
            if ($weeklyRate > 0) { $durationDays = (int) round(abs($deltaKg) / $weeklyRate * 7); }
        }

        return new \App\Features\ProgramPlanner\Domain\ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            macro_ratios: ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: $durationDays,
            meta: [
                'bmr' => (int) round($bmr),
                'pal' => $pal,
                'tdee' => (int) round($tdee),
                'goal_adjust_kcal' => $goalAdjust,
                'goal_code' => 'weight_loss',
                'weekly_pct' => $def['weekly_pct'],
                'weekly_kg' => $def['weekly_kg'],
                'max_weekly_kg' => $def['max_weekly_kg'],
                'cap_pct'   => $def['cap_pct'] ?? null,
                'capped_by' => $def['capped_by'] ?? null,
            ],
        );
    }

    /** Goal 4: Gain muscles */
    private function planForMuscleGain(CreateProgramData $d, float $tdee, float $bmr, float $pal): \App\Features\ProgramPlanner\Domain\ProgramPlan
    {
        // Surplus piloté par hebdoGain (= hebdoLoss), cap non-conservateur (contrairement à recomp)
        $sur = $this->dailySurplusFromHebdoGain($d, $tdee, false);
        $dailyKcal = (int) round($tdee + $sur['adjust']);


        // --- Calorie cycling optionnel jours entraînés / jours repos (même moyenne hebdo) ---
        $trainDays = max(0, min((count($d->trainingDaysOfWeek ?? []) ?? 0), 6));
        $weeklyAvg = null; $trainKcal = null; $restKcal = null;
        if ($trainDays > 0) {
            $restDays  = 7 - $trainDays;

            // Écarter de ±8% du TDEE autour de la cible du jour
            $upShift   = (int) round(0.08 * $tdee);
            $downShift = (int) round(0.08 * $tdee);
            $rawTrain  = $dailyKcal + $upShift;
            $rawRest   = $dailyKcal - $downShift;

            // Garde-fous (jamais trop bas/haut selon les règles globales)
            $rawTrain = $this->clampDailyKcalForSafety($rawTrain, $tdee, $d->gender ?? null);
            $rawRest  = $this->clampDailyKcalForSafety($rawRest,  $tdee, $d->gender ?? null);

            // Rééchelle pour que la moyenne hebdo retombe sur la cible
            $avgNow = ($rawTrain * $trainDays + $rawRest * $restDays) / 7.0;
            if ($avgNow != 0) {
                $scale     = $dailyKcal / $avgNow;
                $trainKcal = (int) round($rawTrain * $scale);
                $restKcal  = (int) round($rawRest  * $scale);

                // Re-clamp post-échelle
                $trainKcal = $this->clampDailyKcalForSafety($trainKcal, $tdee, $d->gender ?? null);
                $restKcal  = $this->clampDailyKcalForSafety($restKcal,  $tdee, $d->gender ?? null);
            } else {
                $trainKcal = $rawTrain;
                $restKcal  = $rawRest;
            }

            $weeklyAvg = (int) round(($trainKcal * $trainDays + $restKcal * $restDays) / 7);
        }

        // Si cycling actif, on remplace la cible journalière par la moyenne hebdo
        if ($weeklyAvg !== null) {
            $dailyKcal = $weeklyAvg;
        }

        // Protéines 1.8 g/kg (bornes 1.6–2.2 g/kg)
        $proteinPerKg = 1.8;
        $proteinG  = (int) round($d->startWeight * $proteinPerKg);
        $proteinKc = $proteinG * 4;
        $protFloorKc = (int) round($d->startWeight * 1.6) * 4; // 1.6 g/kg min
        $protCeilKc  = (int) round($d->startWeight * 2.2) * 4; // 2.2 g/kg max
        $proteinKc   = max($protFloorKc, min($proteinKc, $protCeilKc));

        // Lipides 25% kcal (min 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.25);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($d->startWeight * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // Carbs init = reste
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        // ---- Cibles glucidiques pour perf, adaptées à l'activité et à la fréquence d'entraînement ----
        $activityId = (int) $d->activityLevelId;

        // Plancher absolu "santé/perf"
        $absMinCarbG = 130;

        // Planchers dynamiques g/kg (plus on s'entraîne / est actif, plus le plancher monte)
        $minCarbPerKg = 3.0;                            // base
        if ($activityId >= 6) { $minCarbPerKg = max($minCarbPerKg, 3.5); }
        if ($trainDays >= 4) {  $minCarbPerKg = max($minCarbPerKg, 3.6); }
        $minCarbG = (int) round(max($absMinCarbG, $d->startWeight * $minCarbPerKg));

        // Cible glucidique
        $targetCarbPerKg = 4.5;                         // base hypertrophie
        if ($trainDays >= 4) {  $targetCarbPerKg = 5.0; }
        $targetCarbG = (int) round($d->startWeight * $targetCarbPerKg);
        $minCarbKc   = $minCarbG * 4;

        // ---- LISSAGE MACRO ----
        // 1) Lipides bornés par activité (>=0.8 g/kg) : sédentaire/léger 22–28%, modéré+ 25–32%
        if ($activityId <= 3) { $lipMinPct = 22; $lipMaxPct = 28; }
        else                  { $lipMinPct = 25; $lipMaxPct = 32; }
        $lipMinKc  = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
        $lipMaxKc  = (int) round($dailyKcal * $lipMaxPct / 100);
        $fatKc     = max($lipMinKc, min($fatKc, $lipMaxKc));

        // 2) Protéines capées à ~30% kcal (sans passer sous 1.6 g/kg)
        $protPctCap  = 30;
        $currProtPct = ($proteinKc / max(1, $dailyKcal)) * 100;
        if ($currProtPct > $protPctCap) {
            $targetProtKc = (int) round($dailyKcal * $protPctCap / 100);
            $proteinKc    = max($targetProtKc, $protFloorKc);
        }

        // 3) Glucides = reste, avec plancher dynamique et tentative vers la cible
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
        } else {
            // Si marge, rapprocher de la cible (ex: 5 g/kg quand training régulier) en libérant un peu de lipides (jusqu'à 22–24%)
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

        // Conversion finale en grammes
        $proteinG = (int) round($proteinKc / 4);
        $fatG     = (int) round($fatKc / 9);
        $carbG    = (int) round($carbKc / 4);

        // Ratios finaux
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // Steps & eau
        $steps   = self::STEPS_BY_ACTIVITY[$d->activityLevelId] ?? 8000;
        $waterMl = $this->waterMlFor($d->startWeight, $d->activityLevelId, $d->gender ?? null);
        $profile = self::BASE_PROFILES['gain_muscles'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        return new \App\Features\ProgramPlanner\Domain\ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: [
                'protein' => ['g' => $proteinG, 'kcal' => $proteinKc],
                'fat'     => ['g' => $fatG,     'kcal' => $fatKc],
                'carbs'   => ['g' => $carbG,    'kcal' => $carbKc],
            ],
            macro_ratios: ['protein' => (int)$proteinPct, 'fat' => (int)$fatPct, 'carbs' => (int)$carbPct],
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: null,
            meta: [
                'bmr' => (int) round($bmr),
                'pal' => $pal,
                'tdee' => (int) round($tdee),
                'goal_adjust_kcal' => $sur['adjust'],
                'goal_code' => 'gain_muscles',
                'weekly_avg_kcal' => $weeklyAvg ?? null,
                'train_kcal'      => $trainKcal ?? null,
                'rest_kcal'       => $restKcal ?? null,
                'cap_pct'   => $sur['cap_pct'] ?? null,
                'capped_by' => $sur['capped_by'] ?? null,
            ],
            training_days: $d->trainingDaysOfWeek ?? [],
        );
    }

    private function planForWeightGain(CreateProgramData $d, float $tdee, float $bmr, float $pal): \App\Features\ProgramPlanner\Domain\ProgramPlan
    {
        // Surplus piloté par hebdo (kg/sem) + cap adaptatif TDEE
        $sur = $this->dailySurplusFromHebdoGain($d, $tdee);
        $dailyKcal = (int) round($tdee + $sur['adjust']);

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

        // Plancher absolu glucides (130 g) pour éviter très bas carb en prise de poids
        $absMinCarbG = 130;

        // Carbs init = reste
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        // Enforce absolute carb floor (130 g) with safe rebalance (reduce fats first to lipMinKc, then proteins to 1.6 g/kg)
        $absMinCarbG = $absMinCarbG ?? 130;
        if ($carbG < $absMinCarbG) {
            $needed = ($absMinCarbG * 4) - $carbKc;
            // tentative: réduire lipides vers un plancher temporaire (sera borné à nouveau plus bas)
            $tmpLipMinPct = 20;
            $tmpLipMinKc  = max((int) round($dailyKcal * $tmpLipMinPct / 100), $minFatG * 9);
            if ($fatKc > $tmpLipMinKc) {
                $take = min($needed, $fatKc - $tmpLipMinKc);
                if ($take > 0) { $fatKc -= $take; $carbKc += $take; $needed -= $take; }
            }
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc);
                if ($take > 0) { $proteinKc -= $take; $carbKc += $take; $needed -= $take; }
            }
            $carbG = (int) round($carbKc / 4);
            $fatG  = (int) round($fatKc / 9);
            $proteinG = (int) round($proteinKc / 4);
        }

        // Cibles glucidiques pour la prise de masse saine
        $targetCarbG = (int) round($d->startWeight * 5.0);  // cible (4.5–6 g/kg), on vise 5.0
        $minCarbG    = (int) round($d->startWeight * 3.0);  // plancher absolu
        if ((int)$d->activityLevelId >= 6) {
            $minCarbG = max($minCarbG, (int) round($d->startWeight * 3.5));
        }
        $minCarbKc = $minCarbG * 4;

        // ---- LISSAGE MACRO (bulk) ----
        // 1) Bornes lipides dynamiques selon niveau d'activité
        $activityId = (int)$d->activityLevelId;
        if ($activityId <= 3) {
            $lipMinPct = 22;
            $lipMaxPct = 28;
        } else {
            $lipMinPct = 25;
            $lipMaxPct = 32;
        }
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

        // Durée (si targetWeight > startWeight), basée sur le weekly_kg effectif (pas de fallback)
        $durationDays = null;
        if ($d->targetWeight > 0 && $d->targetWeight > $d->startWeight) {
            $deltaKg = $d->targetWeight - $d->startWeight; // positif (gain)
            $weeklyRate = !empty($sur['weekly_kg']) ? (float) $sur['weekly_kg'] : null;
            if ($weeklyRate > 0) {
                $durationDays = (int) round(($deltaKg / $weeklyRate) * 7);
            }
        }

        return new \App\Features\ProgramPlanner\Domain\ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            macro_ratios: ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: $durationDays,
            meta: [
                'bmr' => (int) round($bmr),
                'pal' => $pal,
                'tdee' => (int) round($tdee),
                'goal_adjust_kcal' => (int) round($dailyKcal - $tdee),
                'goal_code' => 'weight_gain',
                'weekly_kg'  => $sur['weekly_kg'] ?? null,
                'weekly_pct' => $sur['weekly_pct'] ?? null,
                'cap_pct'    => $sur['cap_pct'] ?? null,
                'capped_by'  => $sur['capped_by'] ?? null,
                'requested_weekly_kg' => isset($d->hebdoChange) ? (float) $d->hebdoChange : null,
            ],
        );
    }

    /** Goal 3: Maintain */
    private function planForMaintain(CreateProgramData $d, float $tdee, float $bmr, float $pal): \App\Features\ProgramPlanner\Domain\ProgramPlan
    {
        // Maintenance avec garde-fous identiques à weight_loss (plancher % TDEE + min par sexe)
        $dailyKcal = $this->clampDailyKcalForSafety((int) round($tdee), $tdee, $d->gender ?? null);

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

        return new \App\Features\ProgramPlanner\Domain\ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: [
                'protein' => ['g' => (int)$proteinG, 'kcal' => (int)$proteinKc],
                'fat'     => ['g' => (int)$fatG,     'kcal' => (int)$fatKc],
                'carbs'   => ['g' => (int)$carbG,    'kcal' => (int)$carbKc],
            ],
            macro_ratios: ['protein' => $proteinPct, 'fat' => $fatPct, 'carbs' => $carbPct],
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: null,
            meta: [ 'bmr' => (int) round($bmr), 'pal' => $pal, 'tdee' => (int) round($tdee), 'goal_adjust_kcal' => 0, 'goal_code' => 'maintain' ],
        );
    }

    private function planForRecomp(CreateProgramData $d, float $tdee, float $bmr, float $pal): \App\Features\ProgramPlanner\Domain\ProgramPlan
    {
        // 0) Direction par rapport au poids cible (perte si target < start, gain si target > start)
        $deltaTarget = ($d->targetWeight > 0) ? ($d->targetWeight - $d->startWeight) : 0.0;

        // 1) Point de consigne kcal/J en moyenne hebdo
        //    - si objectif ↘ poids : déficit guidé par hebdoLoss + cap adaptatif (réutilise dailyDeficitFromHebdoLoss)
        //    - si objectif ↗ poids : surplus guidé par hebdoLoss (interprété ici comme hebdoGain) + cap (dailySurplusFromHebdoGain)
        //    - sinon, maintenance "neutre" (recomp sur force/technique sans viser variation pondérale nette)
        $defOrSur = ['adjust' => 0, 'weekly_kg' => 0.0, 'weekly_pct' => 0.0, 'capped_by' => null, 'cap_pct' => null];
        if ($deltaTarget < 0) {
            // cut léger (recomp orientée perte de gras)
            $defOrSur = $this->dailyDeficitFromHebdoLoss($d, $tdee, true);
        } elseif ($deltaTarget > 0) {
            // lean bulk léger (recomp orientée prise de masse nette)
            $defOrSur = $this->dailySurplusFromHebdoGain($d, $tdee, true);
        }
        $dailyKcalTarget = (int) round($tdee + ($defOrSur['adjust'] ?? 0));
        $dailyKcalTarget = $this->clampDailyKcalForSafety($dailyKcalTarget, $tdee, $d->gender ?? null);

        // 2) Calorie cycling optionnel autour de la cible (jours entraînés vs repos)
        $trainDays = max(0, min((count($d->trainingDaysOfWeek ?? []) ?? 0), 6));
        $weeklyAvg = null; $trainKcal = null; $restKcal = null;
        if ($trainDays > 0) {
            $restDays  = 7 - $trainDays;

            // Écarter de ±8% du TDEE autour de la cible, puis re-biaiser pour que la moyenne = cible.
            $upShift   = (int) round(0.08 * $tdee);
            $downShift = (int) round(0.08 * $tdee);
            $rawTrain  = $dailyKcalTarget + $upShift;
            $rawRest   = $dailyKcalTarget - $downShift;

            // Appliquer garde-fous par jour
            $rawTrain = $this->clampDailyKcalForSafety($rawTrain, $tdee, $d->gender ?? null);
            $rawRest  = $this->clampDailyKcalForSafety($rawRest,  $tdee, $d->gender ?? null);

            // Si la moyenne ne retombe pas exactement sur la cible (planchers), applique un petit facteur d'échelle
            $avgNow = ($rawTrain * $trainDays + $rawRest * $restDays) / 7.0;
            if ($avgNow != 0) {
                $scale   = $dailyKcalTarget / $avgNow;
                $trainKcal = (int) round($rawTrain * $scale);
                $restKcal  = (int) round($rawRest  * $scale);

                // Re-clamp post-échelle
                $trainKcal = $this->clampDailyKcalForSafety($trainKcal, $tdee, $d->gender ?? null);
                $restKcal  = $this->clampDailyKcalForSafety($restKcal,  $tdee, $d->gender ?? null);
            } else {
                $trainKcal = $rawTrain;
                $restKcal  = $rawRest;
            }

            $weeklyAvg = (int) round(($trainKcal * $trainDays + $restKcal * $restDays) / 7);
        }

        // Point de consigne final (moyenne hebdo du cycling si présent, sinon cible directe)
        $dailyKcal = $weeklyAvg !== null ? $weeklyAvg : $dailyKcalTarget;
        $dailyKcal = $this->clampDailyKcalForSafety($dailyKcal, $tdee, $d->gender ?? null);

        // 3) Estimation de la variation hebdo implicite depuis l'écart à TDEE (toujours positive en sortie)
        $deltaDailyKcal = $dailyKcal - $tdee; // négatif si léger cut, positif si léger surplus
        $weeklyKg       = round(abs(($deltaDailyKcal * 7) / 7700), 3);
        $weeklyPct      = $d->startWeight > 0 ? round(abs($weeklyKg / $d->startWeight), 4) : null;

        // 4) Protéines 2.0–2.2 g/kg (réf. poids cible si baisse, sinon poids actuel)
        $wRef = ($d->targetWeight > 0 && $d->targetWeight < $d->startWeight) ? $d->targetWeight : $d->startWeight;
        $protPerKg = 2.1; $protFloor = 2.0; $protCeil = 2.4;
        $protPerKg = max($protFloor, min($protCeil, $protPerKg));
        $proteinG  = (int) round($wRef * $protPerKg);
        $proteinKc = $proteinG * 4;

        // 5) Lipides 25–30% kcal (plancher ≥ 0.8 g/kg ou 50 g)
        $fatKc = (int) round($dailyKcal * 0.27);
        $fatG  = (int) round($fatKc / 9);
        $minFatG = max(50, (int) round($wRef * 0.8));
        if ($fatG < $minFatG) { $fatG = $minFatG; $fatKc = $fatG * 9; }

        // 6) Glucides = reste avec planchers liés à l'activité
        $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
        $carbG  = (int) round($carbKc / 4);

        $act = (int) $d->activityLevelId;
        $minCarbPerKg = $act >= 6 ? 3.0 : ($act >= 5 ? 2.5 : 1.5);
        $minCarbG = max(130, (int) round($wRef * $minCarbPerKg));
        $minCarbKc = $minCarbG * 4;

        if ($carbKc < $minCarbKc) {
            $needed = $minCarbKc - $carbKc;
            // a) réduire lipides vers 25% (sans passer sous 0.8 g/kg)
            $lipMinPct = 25; $lipMinKc = max((int) round($dailyKcal * $lipMinPct / 100), $minFatG * 9);
            if ($fatKc > $lipMinKc) { $take = min($needed, $fatKc - $lipMinKc); $fatKc -= $take; $needed -= $take; }
            // b) réduire prot jusqu’à 2.0 g/kg si besoin
            $protFloorKc = (int) round($wRef * 2.0) * 4;
            if ($needed > 0 && $proteinKc > $protFloorKc) {
                $take = min($needed, $proteinKc - $protFloorKc); $proteinKc -= $take; $needed -= $take;
            }
            $carbKc = max(0, $dailyKcal - ($proteinKc + $fatKc));
            $carbG  = (int) round($carbKc / 4);
            $fatG   = (int) round($fatKc / 9);
            $proteinG = (int) round($proteinKc / 4);
        }

        // 7) Ratios macros
        [$proteinPct, $fatPct, $carbPct] = $this->macroRatios($dailyKcal, $proteinKc, $fatKc);

        // 8) Steps & water
        $steps   = $this->stepsFor($d->activityLevelId, $deltaTarget < 0); // plancher doux seulement en direction perte
        $waterMl = $this->waterMlFor($wRef, $d->activityLevelId, $d->gender ?? null);

        // 9) Répartition repas (profil dépend de la direction)
        $profile = $deltaTarget < 0 ? self::BASE_PROFILES['weight_loss'] : self::BASE_PROFILES['gain_muscles'];
        $perMeal = $this->distributeByMealUse($dailyKcal, $d->mealUse, $profile);

        // 10) Durée estimée jusqu'au poids cible, si fourni (rythme EFFECTIF prioritaire)
        $durationDays = null;
        if ($d->targetWeight > 0 && $d->startWeight > 0) {
            $distanceKg = abs($d->targetWeight - $d->startWeight);

            // 1) rythme effectif issu du module déficit/surplus (déjà capé)
            $paceKgPerWeek = (!empty($defOrSur['weekly_kg']) && $defOrSur['weekly_kg'] > 0)
                ? (float) $defOrSur['weekly_kg']
                : null;

            // 2) sinon, rythme implicite depuis l’écart à TDEE après cycling
            if (!$paceKgPerWeek) {
                $deltaDailyKcal = $dailyKcal - $tdee; // négatif si léger cut, positif si surplus
                $paceKgPerWeek  = abs(($deltaDailyKcal * 7) / 7700);
            }

            if ($paceKgPerWeek > 0) {
                $durationDays = (int) round(($distanceKg / $paceKgPerWeek) * 7);
            }
        }

        return new \App\Features\ProgramPlanner\Domain\ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: [
                'protein' => ['g' => $proteinG, 'kcal' => $proteinKc],
                'fat'     => ['g' => $fatG,     'kcal' => $fatKc],
                'carbs'   => ['g' => $carbG,    'kcal' => $carbKc],
            ],
            macro_ratios: ['protein' => (int)$proteinPct, 'fat' => (int)$fatPct, 'carbs' => (int)$carbPct],
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: $durationDays,
            meta: [
                'bmr' => (int) round($bmr),
                'pal' => $pal,
                'tdee' => (int) round($tdee),
                'goal_code' => 'recomp',
                'direction' => $deltaTarget < 0 ? 'loss' : ($deltaTarget > 0 ? 'gain' : 'neutral'),
                'weekly_avg_kcal' => $weeklyAvg,
                'train_kcal' => $trainKcal,
                'rest_kcal'  => $restKcal,
                // Informations issues du module déficit/surplus si utilisé
                'weekly_kg'  => $weeklyKg,
                'weekly_pct' => $weeklyPct,
                'cap_pct'    => $defOrSur['cap_pct'] ?? null,
                'capped_by'  => $defOrSur['capped_by'] ?? null,
                'requested_weekly_kg' => (!empty($d->hebdoChange) && $d->hebdoChange > 0) ? (float) $d->hebdoChange : null,
            ],
            training_days: $d->trainingDaysOfWeek ?? [],
        );
    }
}
