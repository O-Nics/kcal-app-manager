<?php

namespace App\Features\Food\Controllers;




use App\Features\Food\Data\AddBrandData;
use App\Features\Food\Data\IngredientData;
use App\Features\Food\Resources\IngredientResource;
use App\Features\Food\Services\IngredientImporterService;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Ensure\EnsureCustomer;
use App\Support\Repositories\Ingredient\IngredientRepository;
use Illuminate\Http\JsonResponse;
use OpenFoodFacts\Laravel\Facades\OpenFoodFacts;
use Throwable;

class FoodController extends Controller {


   public function addFood()
   {

   }

    public function updateFood()
    {

    }

    public function deleteFood()
    {

    }

    public function getFood()
    {

    }




}
