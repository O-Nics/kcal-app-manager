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
    | Safety floors & caps
    |--------------------------------------------------------------------------
    */
    'min_tdee_ratio'   => 0.70,
    'min_male_kcal'    => 1700,
    'min_female_kcal'  => 1400,
    'min_neutral_kcal' => 1500,

    'deficit_cap_min'          => 0.20,
    'deficit_cap_max'          => 0.27,
    'deficit_cap_hard_ceiling' => 0.35,
    'recomp_cap_tweak_deficit' => -0.02,

    'surplus_cap_pct'          => 0.15,
    'recomp_cap_tweak_surplus' => -0.01,

    /*
    |--------------------------------------------------------------------------
    | Macros (protein / fat)
    |--------------------------------------------------------------------------
    */
    'min_protein_per_kg' => 1.6,
    'max_protein_per_kg' => 2.2,
    'min_fat_per_kg'     => 0.6,
    'max_fat_per_kg'     => 1.2,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */
    'enforce_protein_over_carbs' => false,
];
