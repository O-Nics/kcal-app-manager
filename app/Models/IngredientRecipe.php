<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property-read Ingredient $ingredient
 * @property-read Recipe $recipe
 * @property float $quantity
 */
class IngredientRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'recipe_id',
        'quantity',
    ];

    protected $table = 'ingredient_recipe';

    protected $casts = [
        'id' => 'integer',
        'quantity' => 'float',
    ];

    public function recipe() :\Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
