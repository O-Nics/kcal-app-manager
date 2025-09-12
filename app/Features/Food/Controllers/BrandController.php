<?php

namespace App\Features\Food\Controllers;




use App\Features\Food\Data\AddBrandData;
use App\Http\Resources\Collections\BrandCollection;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Ensure\EnsureCustomer;
use App\Support\Repositories\Brand\BrandRepository;
use App\Support\Repositories\Ingredient\IngredientRepository;
use Illuminate\Http\JsonResponse;
use Throwable;

class BrandController extends Controller {


    private BrandRepository $brandRepository;

    private EnsureCustomer $ensureCustomer;


    public function __construct(
        BrandRepository $brandRepository,
        EnsureCustomer $ensureCustomer,
    ) {
        $this->brandRepository = $brandRepository;
        $this->ensureCustomer = $ensureCustomer;
    }

    /**
     * @throws Throwable
     */


    public function getBrands(Customer $customer): JsonResponse
    {
        $brands = Brand::query()
            ->withCount('ingredients')
            ->orderByRaw(
                'CASE
            WHEN customer_id = ? THEN 2
            WHEN customer_id IS NULL THEN 1
            ELSE 0
        END DESC',
                [$customer->id]
            )
            ->orderByDesc('ingredients_count')
            ->orderBy('name')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'brands' => new BrandCollection($brands,$customer),
        ]);
    }


    public function addBrand(Customer $customer, AddBrandData $addBrandData): JsonResponse
    {

        $brand = $this->brandRepository->addBrand($addBrandData, $customer);

        return response()->json([
            'success' => true,
            'brand' => new BrandsResource($brand,$customer),
        ]);

    }

    public function deleteBrand(Customer $customer, Brand $brand): JsonResponse
    {

        $this->ensureCustomer->userCanManageCustomer(null, $customer);
        $this->ensureCustomer->customerCanManageBrand($customer, $brand);

        $this->brandRepository->deleteBrand($brand);

        return response()->json([
            'success' => true,
        ]);

    }





}
