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
    public function getIngredient(Customer $customer, ?Ingredient $ingredient): JsonResponse
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

    public function getIngredientByBarCode(Customer $customer, string $barcode): JsonResponse
    {

        // Vérification de la validité du code-barres
        if (!Ingredient::isBarcode($barcode)) {
            return response()->json([
                'success' => false,
                'message' => 'Code barre invalide',
            ], 400);
        }

        // Recherche de l'ingrédient dans la base de données
        $ingredient = $this->ingredientRepository->findIngredientByBarCode($barcode);

        if ($ingredient) {
            return response()->json([
                'success' => true,
                'ingredient' => new IngredientResource($ingredient, $customer, false),
            ]);
        }

        // Recherche via OpenFoodFacts
        $externalIngredient = OpenFoodFacts::barcode($barcode);

        if ($externalIngredient) {
            $createdIngredient = new IngredientImporterService()->processBatch($externalIngredient);

            if($createdIngredient == null){
                return response()->json([
                    'success' => false,
                    'message' => 'Aliment non trouvé',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'ingredient' => new IngredientResource($createdIngredient, $customer, false),
            ]);
        }

        // Si aucun ingrédient trouvé
        return response()->json([
            'success' => false,
            'message' => 'Aliment non trouvé',
        ], 404);
    }




}
