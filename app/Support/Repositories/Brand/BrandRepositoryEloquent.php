<?php

namespace App\Support\Repositories\Brand;

use App\Features\Food\Data\AddBrandData;
use App\Models\Brand;
use App\Models\Customer;
use App\Support\Repositories\BaseRepository;


class BrandRepositoryEloquent extends BaseRepository implements BrandRepository {

    protected string $model = Brand::class;

    public function addBrand(AddBrandData $addBrandData, Customer $customer): Brand
    {

        $brand = $this->query()->where('name', $addBrandData->name)
            ->first();

        if(!$brand) {
            $brand = new Brand();
            $brand->name = $addBrandData->name;
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



}
