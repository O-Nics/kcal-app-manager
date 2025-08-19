<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ObjectiveKcalMealType
 *
 * @property int $id
 * @property string $customer_id
 * @property int $meal_type_id
 * @property int $kcal
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Customer $customer
 * @property-read MealType $mealType
 */

class ObjectiveKcalMealType extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'meal_type_id',
        'kcal',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'kcal' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function mealType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MealType::class);
    }

}
