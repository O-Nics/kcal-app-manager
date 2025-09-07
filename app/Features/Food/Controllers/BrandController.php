<?php

namespace App\Features\Food\Controllers;




use App\Features\Food\Data\AddBrandData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
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


    public function addBrand(Customer $customer, AddBrandData $addBrandData): JsonResponse
    {

        $brand = $this->brandRepository->addBrand($addBrandData, $customer);

        return response()->json([
            'success' => true,
            'brand' => $brand
//            'brand' => new BrandsResource($brand,$customer),
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
