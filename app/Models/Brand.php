<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * * @property int $id
 * * @property string $name
 * * @property string $name_norm
 * * @property bool $is_verified
 * * @property string $customer_id
 * * @property-read  Customer $customer
 * */
class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_verified', 'customer_id','name_norm'];


    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function ingredients(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'brand_ingredients');
    }

    public static function makeNameNorm($brandName): string
    {
        return (string) Str::of($brandName)->lower()->ascii()->trim();
    }
    //
}
