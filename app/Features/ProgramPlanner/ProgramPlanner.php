<?php

namespace App\Features\ProgramPlanner;

use App\Features\ProgramPlanner\Contracts\ProgramEngine;
use App\Features\ProgramPlanner\Domain\ProgramPlan;
use App\Features\ProgramPlanner\Domain\ProgramRequest;

final class ProgramPlanner
{
    public function __construct(private readonly ProgramEngine $engine) {}

    public function build(ProgramRequest $r): ProgramPlan
    {
        $bmr  = $this->engine->computeBmr($r);
        $tdee = $this->engine->computeTdee($bmr, $r->activityLevelId);

        // pick adjustment
        [$adjKcal, $adjMeta] = match ($r->goal) {
            'weight_loss'            => $this->engine->computeDeficitPerDay($r, $tdee),
            'weight_gain', 'gain_muscles' => $this->engine->computeSurplusPerDay($r, $tdee),
            default                  => [0, ['reason' => 'maintain']],
        };


//        dd($adjKcal, $adjMeta, $bmr, $tdee);

        $candidateDaily = (int) round($tdee + $adjKcal);
        $dailyKcal      = $this->engine->clampDailyKcal($tdee, $candidateDaily, $r->sex);

        $macroData      = $this->engine->planMacros($dailyKcal, $r);
        $profile        = $r->mealProfile;     // déjà résolu côté controller/policy
        $distribution   = $this->engine->distributeMeals($dailyKcal, $r->mealUse, $profile);
        $waterMl        = $this->engine->hydrationMl($r);
        $steps          = $this->engine->steps($r, cutFloor: true);
        $durationDays   = $this->engine->estimateDurationDays($r->targetWeight, $r->startWeight, $r->weeklyKg);

        // Map Distribution -> per_meal_kcal array
        $perMeal = [];
        if (isset($distribution)) {
            if (is_array($distribution)) {
                $perMeal = $distribution; // already an array
            } elseif (is_object($distribution)) {
                // Adapt to your VO: if it has a public property or a getter
                if (property_exists($distribution, 'kcalBySlot')) {
                    $perMeal = $distribution->kcalBySlot;
                } elseif (method_exists($distribution, 'toArray')) {
                    $perMeal = $distribution->toArray();
                }
            }
        }

        $pal = $bmr > 0 ? ($tdee / $bmr) : null;

        return new ProgramPlan(
            daily_kcal: $dailyKcal,
            macronutrients: $macroData['macros'],   // Macros VO (or array supported by ProgramPlan)
            macro_ratios:   $macroData['ratios'],   // MacroRatios VO (or array supported by ProgramPlan)
            daily_steps_goal: $steps,
            daily_water_goal_ml: $waterMl,
            per_meal_kcal: $perMeal,
            duration_days: $durationDays,
            meta: [
                'bmr' => (int) round($bmr),
                'pal' => $pal,
                'tdee'=> (int) round($tdee),
                'adjust' => $adjMeta,
                ...($macroData['meta'] ?? []),
                'duration_days' => $durationDays,
            ],
            training_days: $r->trainDays ?? null,
        );
    }
}
