<?php

namespace App\Features\ProgramPlanner\Policy;


use App\Features\ProgramPlanner\Contracts\PolicyProvider;
use App\Features\ProgramPlanner\Contracts\ProgramEngine;
use App\Features\ProgramPlanner\Domain\Distribution;
use App\Features\ProgramPlanner\Domain\MacroItem;
use App\Features\ProgramPlanner\Domain\MacroRatios;
use App\Features\ProgramPlanner\Domain\Macros;
use App\Features\ProgramPlanner\Domain\ProgramRequest;

final class DefaultProgramEngine implements ProgramEngine
{
    public function __construct(private readonly PolicyProvider $policy) {}

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
        $capPct  = $this->policy->deficitCapPct($r->bmi, $r->activityLevelId, $r->isRecomp());
        $kcal    = (int) round(-$tdee * $capPct); // negative (deficit)
        return [$kcal, ['cap_pct' => $capPct]];
    }

    public function computeSurplusPerDay(ProgramRequest $r, float $tdee): array
    {
        $capPct = $this->policy->surplusCapPct($r->bmi, $r->activityLevelId, $r->isRecomp());
        $kcal   = (int) round($tdee * $capPct); // positive (surplus)
        return [$kcal, ['cap_pct' => $capPct]];
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
        // mealUse: ['breakfast'=>true, ...]  | profile: ['breakfast'=>0.25, ...]
        $dist = [];
        $sum  = 0.0;
        foreach ($profile as $slot => $pct) {
            if (!empty($mealUse[$slot])) {
                $kc = (int) round($dailyKcal * $pct);
                $dist[$slot] = $kc;
                $sum += $kc;
            }
        }
        // reconcile to dailyKcal
        if ((int)$sum !== $dailyKcal && !empty($dist)) {
            // adjust dinner (or the last) to match exactly
            $last = array_key_last($dist);
            $dist[$last] += ($dailyKcal - (int)$sum);
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
            $steps = max($steps, 8000);
        }
        return $steps;
    }

    public function estimateDurationDays(?float $targetWeight, float $startWeight, ?float $weeklyKg): ?int
    {
        if ($targetWeight === null || $weeklyKg === null || $weeklyKg == 0.0) return null;
        $delta = $targetWeight - $startWeight;
        $perWeek = $weeklyKg; // positive for gain, negative for loss
        $weeks = $delta / $perWeek;
        return $weeks > 0 ? (int) round($weeks * 7) : null;
    }
}
