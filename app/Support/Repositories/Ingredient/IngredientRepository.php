<?php

namespace App\Support\Repositories\Ingredient;

use App\Features\Food\Data\AddBrandData;
use App\Features\Food\Data\IngredientData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use Illuminate\Support\Collection;
use Throwable;

interface IngredientRepository
{

    public function createIngredient(IngredientData $ingredientData, ?Customer $customer = null): Ingredient;

    public function updateIngredient(IngredientData $ingredientData, Ingredient $ingredient): Ingredient;

    public function deleteIngredient(Ingredient $ingredient): void;

    public function findIngredientByBarCode(string $barcode): ?Ingredient;

}
