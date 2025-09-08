<?php

namespace App\Features\Food\Data;

use App\Support\Data\ImageData;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class IngredientData extends Data
{



    #[Required, Exists('ingredient_types', 'id')]
    public int $ingredientTypeId;

    #[Required, Max(255)]
    public string $name;

    #[Required, Max(16)]
    public string $productQuantityUnit;

    #[Nullable]
    public ?ImageData $file;
    #[Nullable]
    public ?float $productQuantity;

    #[Nullable]
    public ?array $brandIds;


    #[Nullable]
    public ?string $barcode;

    #[Required, Min(1)]
    public int $referenceValue;
    #[Nullable]
    public ?string $conservationConditions;
    #[Nullable]
    public ?array $ingredients;

    // Energy (per 100g)
    #[Nullable]
    public ?float $energyKcal100g;
    #[Nullable]
    public ?float $energyKj100g;

    // Macros (per 100g)
    #[Nullable]
    public ?float $fat;
    #[Nullable]
    public ?float $saturatedFat;
    #[Nullable]
    public ?float $sugars;
    #[Nullable]
    public ?float $carbs;
    #[Nullable]
    public ?float $fiber;
    #[Nullable]
    public ?float $proteins;
    #[Nullable]
    public ?float $salt;
    #[Nullable]
    public ?float $sodium;

    // Minerals
    #[Nullable] public ?float $calcium;
    #[Nullable] public ?float $iron;
    #[Nullable] public ?float $copper;
    #[Nullable] public ?float $potassium;
    #[Nullable] public ?float $magnesium;
    #[Nullable] public ?float $manganese;
    #[Nullable] public ?float $phosphorus;
    #[Nullable] public ?float $zinc;
    #[Nullable] public ?float $selenium;
    #[Nullable] public ?float $iodine;
    #[Nullable] public ?float $fluoride;
    #[Nullable] public ?float $chromium;
    #[Nullable] public ?float $cobalt;
    #[Nullable] public ?float $molybdenum;
    #[Nullable] public ?float $chloride;
    // Rare
    #[Nullable] public ?float $arsenic;
    #[Nullable] public ?float $boron;
    #[Nullable] public ?float $rubidium;
    #[Nullable] public ?float $tin;
    #[Nullable] public ?float $vanadium;

    // Vitamins
    #[Nullable] public ?float $vitaminA;
    #[Nullable] public ?float $vitaminD;
    #[Nullable] public ?float $vitaminE;
    #[Nullable] public ?float $vitaminK;
    #[Nullable] public ?float $vitaminC;
    #[Nullable] public ?float $vitaminB1;
    #[Nullable] public ?float $vitaminB2;
    #[Nullable] public ?float $vitaminB3;
    #[Nullable] public ?float $pantothenicAcid;
    #[Nullable] public ?float $vitaminB6;
    #[Nullable] public ?float $vitaminB9;
    #[Nullable] public ?float $vitaminB12;
    #[Nullable] public ?float $biotin;
    #[Nullable] public ?float $choline;

    // Lipids detailed
    #[Nullable] public ?float $monoFat;
    #[Nullable] public ?float $polyFat;
    #[Nullable] public ?float $transFat;
    #[Nullable] public ?float $omega3Ala;
    #[Nullable] public ?float $omega3Epa;
    #[Nullable] public ?float $omega3Dha;
    #[Nullable] public ?float $omega6La;

    // Sugars detailed
    #[Nullable] public ?float $lactose;
    #[Nullable] public ?float $maltose;
    #[Nullable] public ?float $fructose;
    #[Nullable] public ?float $glucose;
    #[Nullable] public ?float $sucrose;
    #[Nullable] public ?float $galactose;
    #[Nullable] public ?float $polyols;
    #[Nullable] public ?float $starch;

    // Others
    #[Nullable] public ?float $cholesterol;
    #[Nullable] public ?float $water;
    #[Nullable] public ?float $alcohol;
    #[Nullable] public ?float $caffeine;



}
