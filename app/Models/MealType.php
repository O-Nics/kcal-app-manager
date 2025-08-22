<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * @property int $id
 * @property string $name
 * @property string $code
 */

class MealType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
    ];
    protected $table = 'meal_types';
    protected $casts = [
        'id' => 'integer',
        'code' => 'integer',
    ];
}
