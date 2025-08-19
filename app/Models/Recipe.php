<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Customer;

/**
 * Class Recipe
 *
 * @property int $id
 * @property int $created_by
 * @property string $name
 * @property string|null $description
 * @property string|null $difficulty
 * @property array|null $steps
 * @property array|null $tags
 * @property int|null $portion
 * @property float|null $time
 * @property bool $verified
 * @property bool $is_private
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'difficulty',
        'steps',
        'tags',
        'portion',
        'time',
        'verified',
        'is_private',
    ];

    protected $casts = [
        'steps' => 'array',
        'tags' => 'array',
        'verified' => 'boolean',
        'is_private' => 'boolean',
        'portion' => 'integer',
        'time' => 'float',
    ];

    public function creator()
    {
        return $this->belongsTo(Customer::class, 'created_by');
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit')
            ->withTimestamps();
    }
}
