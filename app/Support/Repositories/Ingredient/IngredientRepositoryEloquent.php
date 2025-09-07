<?php

namespace App\Support\Repositories\Ingredient;

use App\Models\Ingredient;
use App\Support\Repositories\BaseRepository;
use App\Support\Repositories\Document\DocumentRepository;


class IngredientRepositoryEloquent extends BaseRepository implements IngredientRepository {

    protected string $model = Ingredient::class;


}
