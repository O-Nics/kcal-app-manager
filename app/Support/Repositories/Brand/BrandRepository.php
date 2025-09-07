<?php

namespace App\Support\Repositories\Brand;

use App\Features\Food\Data\AddBrandData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Support\Repositories\Ingredient\AddFoodAteDTO;
use App\Support\Repositories\Ingredient\AddPictureIngredientDTO;
use App\Support\Repositories\Ingredient\DeleteIngredientOrRecipeDTO;
use App\Support\Repositories\Ingredient\IngredientDTO;
use App\Support\Repositories\Ingredient\IntelligentResearchIngredientDTO;
use Illuminate\Support\Collection;
use Throwable;

interface BrandRepository
{
    /**
     * @throws Throwable
     */

    public function addBrand(AddBrandData $brandDTO, Customer $customer): Brand;
    public function deleteBrand(Brand $brand): void;

}
