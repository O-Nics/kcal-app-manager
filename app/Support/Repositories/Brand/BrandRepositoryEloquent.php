<?php

namespace App\Support\Repositories\Brand;

use App\Features\Food\Data\AddBrandData;
use App\Models\Brand;
use App\Models\Customer;
use App\Support\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use phpDocumentor\Reflection\Types\This;


class BrandRepositoryEloquent extends BaseRepository implements BrandRepository {

    protected string $model = Brand::class;

    public function addBrand(AddBrandData $addBrandData, Customer $customer): Brand
    {

        $brand = $this->query()->where('name', $addBrandData->name)
            ->first();

        if(!$brand) {
            $brand = new Brand();
            $brand->name = $addBrandData->name;
            $brand->name_norm = Brand::makeNameNorm($addBrandData->name);
            $brand->is_verified = false;
            $brand->customer_id = $customer->id;
            $brand->save();
        }

        else {
            abort(409, 'Cette marque existe déjà');
        }
        $brand->searchable();
        return $brand;
    }

    public function deleteBrand(Brand $brand): void
    {
        $brand->delete();
    }


    public function getBrands(Customer $customer): LengthAwarePaginator
    {
        return $this->query()
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
    }
}
