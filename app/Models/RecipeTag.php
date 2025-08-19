<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** * Class RecipeTag
 *
 * @property int $id
 * @property-read Recipe $recipe
 * @property-read Tag $tag
 */
class RecipeTag extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'recipe_id',
        'tag_id',
    ];

    protected $table = 'recipe_tags';

    public function recipe(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function tag(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
