<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\Food
 *
 * @property string $id
 * @property string $customer_id
 * @property string $foodable_id
 * @property string $foodable_type
 * @property float $quantity
 * @property string $unit
 * @property int $meal_type_id
 * @property Carbon $added_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Customer $customer
 * @property-read MealType $mealType
 */
class Food extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'foodable_id',
        'foodable_type',
        'quantity',
        'unit',
        'meal_type_id',
        'added_at'
    ];

    protected $casts = [
        'quantity' => 'float',
        'added_at' => 'datetime',
    ];
    public function foodable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function mealType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }
}
