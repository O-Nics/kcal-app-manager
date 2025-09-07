<?php

namespace App\Features\Food\Controllers;




use App\Features\Food\Data\AddBrandData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Support\Abstract\Controller;
use App\Support\Ensure\EnsureCustomer;
use App\Support\Repositories\Ingredient\IngredientRepository;
use Illuminate\Http\JsonResponse;

class IngredientController extends Controller {


    private IngredientRepository $ingredientRepository;

    private EnsureCustomer $ensureCustomer;

    public function __construct(
        IngredientRepository $ingredientRepository,
        EnsureCustomer $ensureCustomer,
    ) {
        $this->ingredientRepository = $ingredientRepository;
        $this->ensureCustomer = $ensureCustomer;
    }
    public function getIngredientById(Customer $customer, ?Ingredient $ingredient): JsonResponse
    {

        return response()->json([
            'success' => true,
            'ingredient' => new IngredientResource($ingredient, $customer, true),
        ]);
    }


}
