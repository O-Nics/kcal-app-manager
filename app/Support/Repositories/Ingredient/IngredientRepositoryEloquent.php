<?php

namespace App\Support\Repositories\Ingredient;

use App\Features\Food\Data\IngredientData;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Support\Data\ImageData;
use App\Support\Helpers\CharHelper;
use App\Support\Repositories\BaseRepository;
use App\Support\Repositories\Document\DocumentRepository;
use Illuminate\Support\Arr;
use Spatie\LaravelData\DataCollection;


class IngredientRepositoryEloquent extends BaseRepository implements IngredientRepository {

    protected string $model = Ingredient::class;

    protected DocumentRepository $documentRepository;

    public function __construct(DocumentRepository $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }

    public function createIngredient(IngredientData $ingredientData, ?Customer $customer = null): Ingredient
    {
        $ingredient = new Ingredient();

        if ($customer) {
            $ingredient->customer_id = $customer->id;
        }

        return $this->updateIngredient($ingredientData, $ingredient);
    }

    public function updateIngredient(IngredientData $ingredientData, Ingredient $ingredient): Ingredient
    {
        $payload = (new CharHelper)->snakeKeys($ingredientData->toArray());

        $ingredient->fill(Arr::only($payload, $ingredient->getFillable()));

        $ingredient->save();

        if (array_key_exists('brand_ids', $payload) && is_array($payload['brand_ids'])) {
            foreach ($payload['brand_ids'] as $brandId) {
                $brand = Brand::find($brandId);
                if($brand) $ingredient->brands()->syncWithoutDetaching($brand);
            }
        }
        if($ingredientData->file != null){
            if($ingredient->image != null){
                $this->documentRepository->delete($ingredient->image);
            }
            $this->documentRepository->store([$ingredientData->file], $ingredient, 'cover');
        }


        return $ingredient->refresh();
    }


    public function deleteIngredient(Ingredient $ingredient): void
    {
        $ingredient->unsearchable();
        $ingredient->delete();
    }


    public function findIngredientByBarCode(string $barcode): ?Ingredient
    {
        /** @var Ingredient $ingredient */
        $ingredient = Ingredient::query()->where('barcode', '=', $barcode)->first();

        return $ingredient;
    }
}
