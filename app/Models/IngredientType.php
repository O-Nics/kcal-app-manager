<?php

namespace App\Models;

use App\Support\Traits\DocumentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int|null $image_id
 */
class IngredientType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function storagePath(): string
    {
        return 'ingredient_types/' . $this->id;

    }
}
