<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * Class Tag
 *
 * @property int $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Tag extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $fillable = ['name'];
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
    ];
    public function recipes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tag');
    }
}
