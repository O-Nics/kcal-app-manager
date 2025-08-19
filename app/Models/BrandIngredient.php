<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * App\Models\BrandIngredient
 *
 * @property int $id
 * @property int $brand_id
 * @property int $ingredient_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Brand $brand
 * @property-read Ingredient $ingredient
 */
class BrandIngredient extends Model
{
    protected $fillable = ['brand_id', 'ingredient_id'];
    protected $table = 'brand_ingredients';

    public function brand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function ingredient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
