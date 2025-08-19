<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * App\Models\Favorite
 *
 * @property int $id
 * @property string $customer_id
 * @property int $favoriteable_id
 * @property string $favoriteable_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Customer $customer
 */
class Favorite extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'favoriteable_id', 'favoriteable_type'];
    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function favoriteable()
    {
        return $this->morphTo();
    }
}
