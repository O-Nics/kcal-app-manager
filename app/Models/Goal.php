<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Goal
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $code
 */

class Goal extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'code'];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'code' => 'integer',
    ];
}
