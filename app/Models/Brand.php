<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * * @property int $id
 * * @property string $name
 * * @property bool $is_verified
 * * @property string $customer_id
 * * @property-read  Customer $customer
 * */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_verified', 'customer_id'];


    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ingredients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'brand_ingredient');
    }
    //
}
