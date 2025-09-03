<?php

namespace App\Features\ProgramPlanner\Nutrition\Domain;

use App\Features\Customer\Services\ProgramPlanning;

final readonly class ProgramPlan implements \JsonSerializable
{
    public int $daily_kcal;
    public Macros $macronutrients;
    public MacroRatios $macro_ratios;
    public int $daily_steps_goal;
    public int $daily_water_goal_ml;
    public array $per_meal_kcal;
    public ?int $duration_days;
    public MetaData $meta;
    public ?array $training_days;

    public function __construct(
        int             $daily_kcal,
        Macros|array    $macronutrients,
        MacroRatios|array $macro_ratios,
        int             $daily_steps_goal,
        int             $daily_water_goal_ml,
        array           $per_meal_kcal,
        ?int            $duration_days,
        MetaData|array  $meta,
        ?array          $training_days = null,
    ) {
        $this->daily_kcal = $daily_kcal;
        $this->macronutrients = $macronutrients instanceof Macros ? $macronutrients : Macros::fromArray($macronutrients);
        $this->macro_ratios   = $macro_ratios instanceof MacroRatios ? $macro_ratios : MacroRatios::fromArray($macro_ratios);
        $this->daily_steps_goal   = $daily_steps_goal;
        $this->daily_water_goal_ml= $daily_water_goal_ml;
        $this->per_meal_kcal      = $per_meal_kcal;
        $this->duration_days      = $duration_days;
        $this->meta               = $meta instanceof MetaData ? $meta : MetaData::fromArray($meta);
        $this->training_days      = $training_days;
    }

    // Aliases / helpers
    public function macroRatios(): MacroRatios
    {
        return $this->macro_ratios;
    }

    public function macro_ratio(): MacroRatios
    {
        return $this->macro_ratios;
    }

    public function macros(): Macros
    {
        return $this->macronutrients;
    }

    public function perMeal(): array
    {
        return $this->per_meal_kcal;
    }

    public function waterMl(): int
    {
        return $this->daily_water_goal_ml;
    }

    public function steps(): int
    {
        return $this->daily_steps_goal;
    }

    public function jsonSerialize(): array
    {
        return [
            'daily_kcal'        => $this->daily_kcal,
            'macronutrients'    => $this->macronutrients->jsonSerialize(),
            'macro_ratios'      => $this->macro_ratios->jsonSerialize(),
            'daily_steps_goal'  => $this->daily_steps_goal,
            'daily_water_goal_ml'=> $this->daily_water_goal_ml,
            'per_meal_kcal'     => $this->per_meal_kcal,
            'duration_days'     => $this->duration_days,
            'training_days'     => $this->training_days,
            'meta'              => $this->meta->jsonSerialize(),
        ];
    }
}
