<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Level (PAL values)
    |--------------------------------------------------------------------------
    */
    'activity_levels' => [
        1 => 1.10, // bedrest
        2 => 1.20, // sedentary
        3 => 1.35, // light walk
        4 => 1.45, // light sport
        5 => 1.55, // moderate
        6 => 1.60, // moderate sport
        7 => 1.70, // active
        8 => 1.85, // very active
    ],

    /*
    |--------------------------------------------------------------------------
    | Steps recommendations
    |--------------------------------------------------------------------------
    */
    'steps' => [
        1 => 1500,
        2 => 3000,
        3 => 6000,
        4 => 8000,
        5 => 9000,
        6 => 10000,
        7 => 12000,
        8 => 14000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meal profiles (base distribution %)
    |--------------------------------------------------------------------------
    */
    'profiles' => [
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety floors & caps (global)
    |--------------------------------------------------------------------------
    */
    'min_tdee_ratio'   => 0.70,  // daily kcal >= 70% TDEE
    'min_male_kcal'    => 1700,
    'min_female_kcal'  => 1400,
    'min_neutral_kcal' => 1500,

    // Deficit caps (fraction of TDEE); hard ceiling is a final clamp.
    'deficit_cap_min'          => 0.22,
    'deficit_cap_max'          => 0.33,
    'deficit_cap_hard_ceiling' => 0.35,
    'recomp_cap_tweak_deficit' => -0.02, // stricter on recomp

    // Surplus cap (fraction of TDEE). If you prefer auto by BMI, use the map below.
    'surplus_cap_pct'          => 0.15,
    'recomp_cap_tweak_surplus' => -0.01,

    /*
    |--------------------------------------------------------------------------
    | Optional: caps by BMI band (Engine can prefer these over global)
    |--------------------------------------------------------------------------
    | Use strings for keys to keep it simple; your PolicyProvider can bucket BMI.
    */
    'deficit_cap_by_bmi' => [
        '<25'   => [ 'min' => 0.22, 'max' => 0.30 ],
        '25-30' => [ 'min' => 0.22, 'max' => 0.33 ],
        '>=30'  => [ 'min' => 0.24, 'max' => 0.33 ],
    ],
    'surplus_cap_by_bmi' => [
        '<25'   => 0.15,
        '25-30' => 0.12,
        '>=30'  => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Macros (per-goal rules) — preferred
    |--------------------------------------------------------------------------
    | Keep your original globals below for backward-compat.
    */
    'protein_g_per_kg' => [
        'weight_loss'  => ['min' => 1.8, 'default' => 2.0, 'max' => 2.2],
        'weight_gain'  => ['min' => 1.6, 'default' => 1.8, 'max' => 2.2],
        'gain_muscles' => ['min' => 1.6, 'default' => 1.8, 'max' => 2.2],
        'maintain'     => ['min' => 1.6, 'default' => 1.8, 'max' => 2.2],
        'recomp'       => ['min' => 2.0, 'default' => 2.1, 'max' => 2.4],
    ],
    'protein_pct_hard_cap' => 32, // cap % kcal protein to avoid starving carbs/fat

    'fat_pct_ranges' => [
        'weight_loss'  => ['min' => 20, 'max' => 30],
        'weight_gain'  => ['min' => 22, 'max' => 32],
        'gain_muscles' => ['min' => 22, 'max' => 32],
        'maintain'     => ['min' => 22, 'max' => 35],
        'recomp'       => ['min' => 25, 'max' => 30],
    ],
    'fat_min_g_per_kg' => 0.8,
    'fat_min_abs_g'    => 50,

    'carbs_abs_min_g' => 100, // never go below (brain + training floor)
    'carbs_min_per_kg_by_activity' => [
        'sedentary'    => 1.5,
        'moderate'     => 2.0,
        'active'       => 2.5,
        'very_active'  => 3.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Your original global macro bounds (kept for backward compatibility)
    |--------------------------------------------------------------------------
    */
    'min_protein_per_kg' => 1.6,
    'max_protein_per_kg' => 2.2,
    'min_fat_per_kg'     => 0.6,
    'max_fat_per_kg'     => 1.2,

    /*
    |--------------------------------------------------------------------------
    | Hydration (optional, if you compute it)
    |--------------------------------------------------------------------------
    */
    'hydration_ml_per_kg' => 35,  // baseline; Engine can add activity bonuses
    'hydration_min_ml'    => 1500,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */
    'enforce_protein_over_carbs' => true, // keep protein floors even if carbs drop
];
