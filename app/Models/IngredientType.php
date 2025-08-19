<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int|null $image_id
 */
class IngredientType extends Model implements Documentable
{
    use DocumentTrait, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image_id',
    ];

    public function storagePath(): string
    {
        return 'ingredient_types/' . $this->id;

    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'image_id');
    }
}
