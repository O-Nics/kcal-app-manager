<?php

namespace App\Features\Food\Controllers;




use App\Features\Food\Data\AddBrandData;
use App\Features\Food\Data\IngredientData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Ensure\EnsureCustomer;
use App\Support\Repositories\Ingredient\IngredientRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class IngredientController extends Controller {


    private IngredientRepository $ingredientRepository;

    private EnsureCustomer $ensureCustomer;

    /**
     * @throws Throwable
     */

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

    public function createIngredient(Customer $customer, IngredientData $ingredientData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->ensureCustomer->userCanManageCustomer($user, $customer);

        $ingredient = $this->ingredientRepository->createIngredient($ingredientData, $customer);

        return response()->json([
            'success' => true,
            'ingredient' => new IngredientResource($ingredient, $customer,false),

        ]);

    }

    /**
     * @throws Throwable
     */
    public function updateIngredient(Customer $customer, Ingredient $ingredient, IngredientData $ingredientData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        $this->ensureCustomer->customerCanManageIngredient($customer, $ingredient);

        $ingredient = $this->ingredientRepository->updateIngredient($ingredientData, $ingredient);

        return response()->json([
            'success' => true,
            'ingredient' => new IngredientResource($ingredient, $customer,false),
        ]);

    }

    public function deleteIngredient(Customer $customer, Ingredient $ingredient): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->ensureCustomer->userCanManageCustomer($user, $customer);
        $this->ensureCustomer->customerCanManageIngredient($customer, $ingredient);
        $this->ingredientRepository->deleteIngredient($ingredient);

        return response()->json([
            'success' => true,
        ]);

    }



}
