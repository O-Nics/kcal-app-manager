<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $value
 */

class MealType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'value',
    ];
    protected $table = 'meal_types';
    protected $casts = [
        'id' => 'integer',
        'code' => 'integer',
    ];
}
