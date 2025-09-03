<?php

namespace App\Features\ProgramPlanner\Nutrition\Engine;

use App\Features\ProgramPlanner\Nutrition\Policy\PolicyProvider;

final class ConfigPolicyProvider implements PolicyProvider
{
    public function palFor(int $activity): float
    {
        $pal = config("program.activity_levels.$activity");
        return $pal !== null ? (float)$pal : 1.55; // fallback moderate
    }

    public function stepsFor(int $activity): int
    {
        $steps = config("program.steps.$activity");
        return $steps !== null ? (int)$steps : 8000;
    }

    public function mealProfileFor(string $goal): array
    {
        $key = $this->normalizeGoal($goal);
        $profiles = config('program.profiles', []);
        return $profiles[$key] ?? [
            'breakfast'       => 0.22,
            'snack_morning'   => 0.10,
            'lunch'           => 0.33,
            'snack_afternoon' => 0.10,
            'dinner'          => 0.25,
        ];
    }

    public function deficitCapPct(float $bmi, int $activity, bool $isRecomp): float
    {
        // Prefer BMI-banded caps if present
        $bandCaps = config('program.deficit_cap_by_bmi');
        if (is_array($bandCaps)) {
            $band = $this->bmiBand($bmi);
            if (isset($bandCaps[$band]['max'])) {
                $max = (float)$bandCaps[$band]['max'];
            }
        }

        // Fallback to global min/max window
        $min = (float) config('program.deficit_cap_min', 0.22);
        $max = isset($max) ? $max : (float) config('program.deficit_cap_max', 0.33);

        // Basic interpolation by activity (lightly tighter at low activity)
        $actWeight = $this->activityWeight($activity); // 0..1
        $cap = $min + ($max - $min) * $actWeight;

        if ($isRecomp) {
            $cap += (float) config('program.recomp_cap_tweak_deficit', -0.02);
        }

        // Hard ceiling
        $hard = (float) config('program.deficit_cap_hard_ceiling', 0.35);
        return max(0.10, min($cap, $hard));
    }

    public function surplusCapPct(float $bmi, int $activity, bool $isRecomp): float
    {
        // Prefer BMI-banded cap if present
        $band = $this->bmiBand($bmi);
        $bmiMap = config('program.surplus_cap_by_bmi');
        if (is_array($bmiMap) && isset($bmiMap[$band])) {
            $cap = (float) $bmiMap[$band];
        } else {
            // Fallback to global
            $cap = (float) config('program.surplus_cap_pct', 0.15);
        }

        // Slight activity modulation (more active → can push slightly higher)
        $cap *= (0.95 + 0.1 * $this->activityWeight($activity)); // ~0.95x..1.05x

        if ($isRecomp) {
            $cap += (float) config('program.recomp_cap_tweak_surplus', -0.01);
        }

        return max(0.05, $cap);
    }

    public function minTdeeRatio(): float
    {
        return (float) config('program.min_tdee_ratio', 0.70);
    }

    public function minDailyKcalAbs(?string $sex): int
    {
        $sex = $sex ? strtolower($sex) : null;
        return match ($sex) {
            'male', 'm'   => (int) config('program.min_male_kcal', 1700),
            'female', 'f' => (int) config('program.min_female_kcal', 1400),
            default       => (int) config('program.min_neutral_kcal', 1500),
        };
    }

    public function proteinBoundsGPerKg(string $goal): array
    {
        $key = $this->normalizeGoal($goal);
        $perGoal = config("program.protein_g_per_kg.$key");
        if (is_array($perGoal)) {
            return [
                'min'     => (float) ($perGoal['min'] ?? config('program.min_protein_per_kg', 1.6)),
                'default' => (float) ($perGoal['default'] ?? config('program.min_protein_per_kg', 1.6)),
                'max'     => (float) ($perGoal['max'] ?? config('program.max_protein_per_kg', 2.2)),
                'pct_cap' => (int)   config('program.protein_pct_hard_cap', 40),
            ];
        }

        // Fallback to global bounds
        return [
            'min'     => (float) config('program.min_protein_per_kg', 1.6),
            'default' => (float) config('program.min_protein_per_kg', 1.6),
            'max'     => (float) config('program.max_protein_per_kg', 2.2),
            'pct_cap' => (int)   config('program.protein_pct_hard_cap', 32),
        ];
    }

    public function fatPctRange(string $goal, int $activity): array
    {
        $key = $this->normalizeGoal($goal);
        $range = config("program.fat_pct_ranges.$key");
        $min = (int) ($range['min'] ?? 22);
        $max = (int) ($range['max'] ?? 35);

        // Optionally tighten a bit on very low TDEE/activity (not mandatory)
        // keep as-is for predictability:
        return ['min' => $min, 'max' => $max];
    }

    public function fatMinGPerKg(): float
    {
        return (float) config('program.fat_min_g_per_kg', 0.8);
    }

    public function fatMinAbsG(): int
    {
        return (int) config('program.fat_min_abs_g', 50);
    }

    public function carbsAbsMinG(): int
    {
        return (int) config('program.carbs_abs_min_g', 100);
    }

    public function carbsMinPerKg(int $activity, ?array $trainDays, string $goal): float
    {
        // Map activity to label buckets expected by config
        $label = $this->activityLabel($activity);
        $base = config("program.carbs_min_per_kg_by_activity.$label");

        // Light modulation by training frequency if provided
        $mod = 1.0;
        if ($trainDays !== null && count($trainDays) > 0) {
            $mod += min(0.2, max(0.0, (count($trainDays) - 3) * 0.05)); // +5%/day above 3, capped +20%
        }

        return (float) (($base ?? 1.8) * $mod);
    }

    public function enforceProteinOverCarbs(): bool
    {
        return (bool) config('program.enforce_protein_over_carbs', true);
    }

    // -----------------
    // Helpers
    // -----------------

    private function normalizeGoal(string $goal): string
    {
        $g = strtolower($goal);
        return match ($g) {
            'weight-loss', 'loss', 'cut'  => 'weight_loss',
            'weight-gain', 'bulk'         => 'weight_gain',
            'gain-muscles', 'muscle'      => 'gain_muscles',
            'maintain', 'maintenance'     => 'maintain',
            'recomp', 'recomposition'     => 'recomp',
            default                       => $g,
        };
    }

    private function bmiBand(float $bmi): string
    {
        if ($bmi < 25.0) return '<25';
        if ($bmi < 30.0) return '25-30';
        return '>=30';
    }

    /**
     * Normalized 0..1 weight for activity interpolation.
     */
    private function activityWeight(int $activity): float
    {
        $min = 1; $max = 8;
        $clamped = max($min, min($max, $activity));
        return ($clamped - $min) / ($max - $min); // 0..1
        // e.g. 1 → 0.0, 8 → 1.0
    }

    private function activityLabel(int $activity): string
    {
        return match (true) {
            $activity <= 2 => 'sedentary',
            $activity <= 5 => 'moderate',
            $activity <= 7 => 'active',
            default        => 'very_active',
        };
    }


}
