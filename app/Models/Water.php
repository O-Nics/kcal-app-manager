<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Water extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'added_at',
        'volume'
    ];
    protected $table = 'water_customers';


    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
