<?php

namespace App\Features\ProgramPlanner\Nutrition\Policy;


use App\Features\ProgramPlanner\Nutrition\Domain\Distribution;
use App\Features\ProgramPlanner\Nutrition\Domain\MacroItem;
use App\Features\ProgramPlanner\Nutrition\Domain\MacroRatios;
use App\Features\ProgramPlanner\Nutrition\Domain\Macros;
use App\Features\ProgramPlanner\Nutrition\Domain\ProgramRequest;
use App\Features\ProgramPlanner\Nutrition\Engine\ProgramEngine;

final readonly class DefaultProgramEngine implements ProgramEngine
{
    public function __construct(private PolicyProvider $policy) {}

    // ---------------- Core energy ----------------

    public function computeBmr(ProgramRequest $r): float
    {
        $kg  = (float) $r->startWeight;
        $cm  = (float) $r->height;
        $age = max(0, (int) $r->age);
        $sex = is_string($r->sex) ? strtolower($r->sex) : null;


        $msj = $this->bmrMiffin($kg, $cm, $age, $sex);
        $hb  = $this->bmrHarrisBenedict($kg, $cm, $age, $sex);

        // Moyenne neutre des deux formules
        return ($msj + $hb) / 2.0;
    }

    private function bmrMiffin(float $kg, float $cm, int $age, ?string $sex): float
    {
        $msjMale   = 10.0 * $kg + 6.25 * $cm - 5.0 * $age + 5.0;
        $msjFemale = 10.0 * $kg + 6.25 * $cm - 5.0 * $age - 161.0;
        return match ($sex) {
            'male'   => $msjMale,
            'female' => $msjFemale,
            default  => ($msjMale + $msjFemale) / 2.0,
        };
    }

    private function bmrHarrisBenedict(float $kg, float $cm, int $age, ?string $sex): float
    {
        $hbMale   = 13.397 * $kg + 4.799 * $cm - 5.677 * $age + 88.362;
        $hbFemale =  9.247 * $kg + 3.098 * $cm - 4.330 * $age + 447.593;
        return match ($sex) {
            'male'   => $hbMale,
            'female' => $hbFemale,
            default  => ($hbMale + $hbFemale) / 2.0,
        };
    }


    public function computeTdee(float $bmr, int $activityLevelId): float
    {
        return $bmr * $this->policy->palFor($activityLevelId);
    }

    // ---------------- Adjustments ----------------

    public function computeDeficitPerDay(ProgramRequest $r, float $tdee): array
    {
        // 1) Requested weekly loss (kg/week) – default 0.5
        $requestedWeeklyKg = (isset($r->weeklyKg) && is_numeric($r->weeklyKg))
            ? (float) $r->weeklyKg
            : 0.5;

        // 2..7) Externalized: weekly % cap, BMI fallback, TDEE cap and conversions
        [$weightRef, $weeklyKgByWeight, $cappedBy, $bmi] =
            $this->capWeeklyAndComputeBmi(
                $r,
                $requestedWeeklyKg,
                (float) (\config('program.weekly_loss_pct_cap', 0.010)),
                25.0
            );

        $capPct = (float) $this->policy->deficitCapPct($bmi, (int) $r->activityLevelId, $r->isRecomp());

        [$kcal, $effectiveWeeklyKg, $effectiveWeeklyPct, $maxWeeklyKgByTdee, $capByTdee] =
            $this->applyTdeeCapAndConvert($tdee, $capPct, $weightRef, $weeklyKgByWeight, -1);

        if ($capByTdee) {
            $cappedBy = $capByTdee;
        }

        // --- NEW: train/rest kcal only for recomp or gain_muscles ---

        $goal = is_string($r->goal ?? null) ? strtolower($r->goal) : null;
        $isCyclingGoal = in_array($goal, ['recomp', 'gain_muscles'], true);

        $trainKcal = null;
        $restKcal = null;
        if ($isCyclingGoal && $tdee > 0) {
            ['train_kcal' => $trainKcal, 'rest_kcal' => $restKcal] = $this->trainAndRestKcal($kcal, $tdee, $r);
        }

        return [
            $kcal,
            [
                'cap_pct'             => $capPct,
                'weekly_kg'           => $effectiveWeeklyKg,
                'weekly_pct'          => $effectiveWeeklyPct,
                'requested_weekly_kg' => $requestedWeeklyKg,
                'capped_by'           => $cappedBy,
                'max_weekly_kg'       => $maxWeeklyKgByTdee,
                // only non-null for recomp/gain_muscles with training days
                'train_kcal'          => $trainKcal,
                'rest_kcal'           => $restKcal,
            ],
        ];
    }

    public function computeSurplusPerDay(ProgramRequest $r, float $tdee): array
    {
        // 1) Requested weekly gain (kg/week); default to 0.25 kg/week if not provided
        $requestedWeeklyKg = null;
        if (isset($r->weeklyKg) && is_numeric($r->weeklyKg)) {
            $requestedWeeklyKg = (float) $r->weeklyKg;
        }
        if (!($requestedWeeklyKg > 0)) {
            $requestedWeeklyKg = 0.25; // lean bulk default
        }

        // 2..7) Externalized: weekly % cap, BMI fallback, TDEE cap and conversions
        [$weightRef, $weeklyKgReq, $cappedBy, $bmi] =
            $this->capWeeklyAndComputeBmi(
                $r,
                $requestedWeeklyKg,
                (float) (\config('program.weekly_gain_pct_cap', 0.006)),
                23.0
            );

        $capPct = (float) $this->policy->surplusCapPct($bmi, (int) $r->activityLevelId, $r->isRecomp());

        [$kcal, $effectiveWeeklyKg, $effectiveWeeklyPct, $maxWeeklyKgByTdee, $capByTdee] =
            $this->applyTdeeCapAndConvert($tdee, $capPct, $weightRef, $weeklyKgReq, +1);

        if ($capByTdee) {
            $cappedBy = $capByTdee;
        }
// --- NEW: train/rest kcal only for recomp or gain_muscles ---


        $goal = is_string($r->goal ?? null) ? strtolower($r->goal) : null;
        $isCyclingGoal = in_array($goal, ['recomp', 'gain_muscles'], true);

        if ($isCyclingGoal && $tdee > 0) {
            ['train_kcal' => $trainKcal, 'rest_kcal' => $restKcal] = $this->trainAndRestKcal($kcal, $tdee, $r);
        }

        return [
            $kcal,
            [
                'cap_pct'       => $capPct,
                'weekly_kg'     => $effectiveWeeklyKg,
                'weekly_pct'    => $effectiveWeeklyPct,
                'requested_weekly_kg' => $requestedWeeklyKg,
                'capped_by'     => $cappedBy,
                'train_kcal'    => $trainKcal,
                'rest_kcal'     => $restKcal,
                'max_weekly_kg' => $maxWeeklyKgByTdee,
            ],
        ];
    }

    public function clampDailyKcal(float $tdee, int $candidateDaily, ?string $sex): int
    {
        $minByRatio = (int) round($tdee * $this->policy->minTdeeRatio());
        $minAbs     = $this->policy->minDailyKcalAbs($sex);
        return max($candidateDaily, max($minByRatio, $minAbs));
    }

    // ---------------- Macros & distribution ----------------

    public function planMacros(int $dailyKcal, ProgramRequest $r): array
    {
        // Protein
        $pb   = $this->policy->proteinBoundsGPerKg($r->goal);
        $pG   = max($pb['min'], min($pb['default'], $pb['max'])) * $r->weight;
        $pKc  = (int) round($pG * 4);
        $pPct = (int) round(($pKc / $dailyKcal) * 100);
        $pPct = min($pPct, (int)$pb['pct_cap']);

        // Fat
        $fatRange = $this->policy->fatPctRange($r->goal, $r->activityLevelId);
        $fPct     = max($fatRange['min'], min($fatRange['max'], 28));
        $fKc      = (int) round($dailyKcal * $fPct / 100);
        $fG       = max(
            $this->policy->fatMinAbsG(),
            (int) round(max($this->policy->fatMinGPerKg() * $r->weight, $fKc / 9))
        );
        $fKc      = (int) round($fG * 9);
        $fPct     = (int) round(($fKc / $dailyKcal) * 100);

        // Carbs = remainder but with absolute floors
        $usedKc   = $pKc + $fKc;
        $cKc      = max(0, $dailyKcal - $usedKc);
        $cG       = (int) round($cKc / 4);

        $cAbsMin  = $this->policy->carbsAbsMinG();
        $cMinPerKg= $this->policy->carbsMinPerKg($r->activityLevelId, $r->trainDays, $r->goal);
        $cMinG    = max($cAbsMin, (int) round($cMinPerKg * $r->weight));

        if ($cG < $cMinG) {
            // Enforce carb floor: borrow kcals from fat first, else protein (if allowed)
            $deltaG = $cMinG - $cG;
            $deltaK = $deltaG * 4;

            $takeFromFatK = min($deltaK, max(0, $fKc - (int) round($this->policy->fatMinAbsG() * 9)));
            $fKc -= $takeFromFatK;
            $deltaK -= $takeFromFatK;

            if ($deltaK > 0 && !$this->policy->enforceProteinOverCarbs()) {
                $takeFromProtK = min($deltaK, max(0, $pKc - (int) round($pb['min'] * $r->weight * 4)));
                $pKc -= $takeFromProtK;
                $deltaK -= $takeFromProtK;
            }

            $cKc += ($cMinG * 4) - ($cG * 4);
        }

        // Recompute grams after adjustments
        $pG = (int) round($pKc / 4);
        $fG = (int) round($fKc / 9);
        $cG = (int) round($cKc / 4);

        // Final reconcile to exactly match dailyKcal (adjust carbs)
        $sumKc = $pKc + $fKc + $cKc;
        if ($sumKc !== $dailyKcal) {
            $cKc += ($dailyKcal - $sumKc);
            $cG   = (int) round($cKc / 4);
        }

        $macros = new Macros(
            new MacroItem($pG, $pKc),
            new MacroItem($fG, $fKc),
            new MacroItem($cG, $cKc),
        );
        $ratios = new MacroRatios(
            (int) round($pKc * 100 / $dailyKcal),
            (int) round($fKc * 100 / $dailyKcal),
            (int) round($cKc * 100 / $dailyKcal),
        );

        return ['macros' => $macros, 'ratios' => $ratios, 'meta' => []];
    }

    public function distributeMeals(int $dailyKcal, array $mealUse, array $profile): Distribution
    {
        $mealMap = [
            1 => 'breakfast',
            2 => 'snack_morning',
            3 => 'lunch',
            4 => 'snack_afternoon',
            5 => 'dinner',
        ];

        if (empty($mealUse)) {
            // Fallback: un seul bloc, clé explicite
//            return ['other' => (int) $dailyKcal];
            return new Distribution(['other' => (int) $dailyKcal]);
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

            return new Distribution(['other' => (int) $dailyKcal]);
        }

        // Comptage des occurrences par slot (pour partager la part d'un slot répété)
        $occ = array_count_values($slots);

        // Répartition brute par slot (accumulation)
        $rawBySlot = [];
        foreach ($slots as $slot) {
            $slotShare = $profile[$slot] ?? 0.0;
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
        $dist = [];
        foreach ($rawBySlot as $slot => $ratio) {
            $dist[$slot] = (int) round($dailyKcal * $ratio);
        }
        return new Distribution($dist);
    }

    // ---------------- Lifestyle ----------------

    public function hydrationMl(ProgramRequest $r): int
    {
        $base = (int) round(($r->hydrationMlPerKg ?? config('program.hydration_ml_per_kg', 35)) * $r->weight);
        return max($base, (int) config('program.hydration_min_ml', 1500));
    }

    public function steps(ProgramRequest $r, bool $cutFloor = false): int
    {
        $steps = $this->policy->stepsFor($r->activityLevelId);
        if ($cutFloor && $r->isCut()) {
            $steps = $steps + 750;
        }
        return $steps;
    }

    public function estimateDurationDays(?float $targetWeight, float $startWeight, ?float $weeklyKg): ?int
    {
        if ($targetWeight === null || $weeklyKg === null || $weeklyKg == 0.0) {
            return null;
        }

        $delta   = (float) $targetWeight - (float) $startWeight;
        if ($delta === 0.0) {
            return 0;
        }

        $weeks = abs($delta) / max(0.0001, abs((float) $weeklyKg));


        return (int) ceil($weeks * 7);
    }

    private function trainAndRestKcal(int $kcal, float $tdee, ProgramRequest $r) : array
    {
        $trainKcal = null;
        $restKcal  = null;
        // daily target from deficit
        $dailyTarget = (int) round($tdee + $kcal); // kcal (kcal is negative)

        // how many training days? prefer $r->trainDays, else derive from array
        $trainDays = isset($r->trainDays) ? (int) $r->trainDays
            : (is_array($r->trainingDaysOfWeek ?? null) ? count($r->trainDays) : 0);
        $trainDays = max(0, min($trainDays, 6));
        $restDays  = 7 - $trainDays;

        if ($trainDays > 0) {
            // ±8% around TDEE, then clamp & rescale to keep weekly average == dailyTarget
            $upShift   = (int) round(0.08 * $tdee);
            $downShift = (int) round(0.08 * $tdee);

            $rawTrain = $dailyTarget + $upShift;
            $rawRest  = $dailyTarget - $downShift;

            // safety per day
            $sex = $r->sex ?? null;
            $rawTrain = $this->clampDailyKcal($tdee, $rawTrain, $sex);
            $rawRest  = $this->clampDailyKcal($tdee, $rawRest,  $sex);

            // rescale to maintain the intended weekly average
            $avgNow = ($rawTrain * $trainDays + $rawRest * $restDays) / 7.0;
            if ($avgNow != 0.0) {
                $scale    = $dailyTarget / $avgNow;
                $trainKcal = (int) round($rawTrain * $scale);
                $restKcal  = (int) round($rawRest  * $scale);

                // re-clamp after rescale
                $trainKcal = $this->clampDailyKcal($tdee, $trainKcal, $sex);
                $restKcal  = $this->clampDailyKcal($tdee, $restKcal,  $sex);
            } else {
                $trainKcal = $rawTrain;
                $restKcal  = $rawRest;
            }
        }

        return ['train_kcal' => $trainKcal ?? null, 'rest_kcal' => $restKcal ?? null];


    }

    /**
     * Cap requested weekly change by bodyweight % and compute BMI (with fallback).
     * Returns: [$weightRef, $weeklyKgCapped, $cappedBy, $bmi]
     */
    private function capWeeklyAndComputeBmi(ProgramRequest $r, float $requestedWeeklyKg, float $pctCap, float $bmiDefault): array
    {
        $weightRef = (isset($r->weight) && $r->weight > 0) ? (float) $r->weight : null;
        $weeklyKgCapW = $weightRef ? round($weightRef * $pctCap, 3) : $requestedWeeklyKg;
        $weeklyKgCapped = min($requestedWeeklyKg, $weeklyKgCapW);

        $cappedBy = null;
        if ($requestedWeeklyKg > $weeklyKgCapped) {
            $cappedBy = 'weekly_pct_cap';
        }

        $bmi = (isset($r->bmi) && $r->bmi > 0)
            ? (float) $r->bmi
            : (($weightRef && isset($r->height) && $r->height > 0)
                ? $weightRef / pow(((float) $r->height) / 100.0, 2)
                : $bmiDefault);

        return [$weightRef, $weeklyKgCapped, $cappedBy, $bmi];
    }

    /**
     * Apply TDEE percentage cap and convert weekly kg to signed daily kcal.
     * $sign = +1 for surplus; -1 for deficit.
     * Returns: [$kcal, $effectiveWeeklyKg, $effectiveWeeklyPct, $maxWeeklyKgByTdee, $cappedBy]
     */
    private function applyTdeeCapAndConvert(float $tdee, float $capPct, ?float $weightRef, float $weeklyKgCapped, int $sign): array
    {
        $maxDailyByPct   = $tdee * $capPct * $sign; // signed kcal/day
        $dailyFromWeekly = $sign * ($weeklyKgCapped * 7700.0) / 7.0; // signed kcal/day

        // choose min/max depending on sign
        $kcal = (int) round(
            ($sign > 0)
                ? min($dailyFromWeekly, $maxDailyByPct)
                : max($dailyFromWeekly, $maxDailyByPct)
        );

        $cappedBy = null;
        if (($sign > 0 && $dailyFromWeekly > $maxDailyByPct) || ($sign < 0 && $dailyFromWeekly < $maxDailyByPct)) {
            $cappedBy = 'tdee_cap';
        }

        $effectiveWeeklyKg  = round(abs(($kcal * 7.0) / 7700.0), 3);
        $effectiveWeeklyPct = ($weightRef && $weightRef > 0)
            ? round($effectiveWeeklyKg / $weightRef, 4)
            : null;
        $maxWeeklyKgByTdee  = round(abs(($maxDailyByPct * 7.0) / 7700.0), 3);

        return [$kcal, $effectiveWeeklyKg, $effectiveWeeklyPct, $maxWeeklyKgByTdee, $cappedBy];
    }

}


