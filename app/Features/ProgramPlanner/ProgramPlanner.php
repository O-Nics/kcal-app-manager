<?php

namespace App\Features\ProgramPlanner;

use App\Features\ProgramPlanner\Nutrition\Domain\ProgramPlan;
use App\Features\ProgramPlanner\Nutrition\Domain\ProgramRequest;
use App\Features\ProgramPlanner\Nutrition\Engine\ProgramEngine;

final readonly class ProgramPlanner
{
    public function __construct(
        private ProgramEngine $engine,
    ) {}

    public function plan(ProgramRequest $r): ProgramPlan
    {
        $bmr  = $this->engine->computeBmr($r);
        $tdee = $this->engine->computeTdee($bmr, $r->activityLevelId);

        // pick adjustment
        [$adjKcal, $adjMeta] = match ($r->goal) {
            'weight_loss' , 'recomp'           => $this->engine->computeDeficitPerDay($r, $tdee),
            'weight_gain', 'gain_muscles' => $this->engine->computeSurplusPerDay($r, $tdee),
            default                  => [0, ['reason' => 'maintain']],
        };

        $candidateDaily = (int) round($tdee + $adjKcal);
        $dailyKcal      = $this->engine->clampDailyKcal($tdee, $candidateDaily, $r->sex);
        $macroData      = $this->engine->planMacros($dailyKcal, $r);
        $profile        = $r->mealProfile;     // déjà résolu côté controller/policy
        $distribution   = $this->engine->distributeMeals($dailyKcal, $r->mealUse, $profile);
        $waterMl        = $this->engine->hydrationMl($r);
        $steps          = $this->engine->steps($r, cutFloor: true);
        $durationDays   = $this->engine->estimateDurationDays($r->targetWeight, $r->startWeight, $r->weeklyKg);

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
                'goal_adjust_kcal' => $adjKcal,
                'goal_code' => $r->goal,
                'weekly_kg' => $adjMeta['weekly_kg'] ?? null,
                'max_weekly_kg' => $adjMeta['max_weekly_kg'] ?? null,
                'cap_pct' => $adjMeta['cap_pct'] ?? null,
                'capped_by' => $adjMeta['capped_by'] ?? null,
                'train_kcal' => $adjMeta['train_kcal'] ?? null,
                'rest_kcal' => $adjMeta['rest_kcal'] ?? null,
                ...($macroData['meta'] ?? []),
                'duration_days' => $durationDays,
            ],
            training_days: $r->trainDays ?? null,
        );
    }
}
