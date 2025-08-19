<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/**
 * * @property int $id
 * * @property string $customer_id
 * * @property float $customer_weight
 * * @property Carbon $date
 * * @property Carbon|null $created_at
 * * @property Carbon|null $updated_at
 * * @property-read Customer $customer
 * */


class BodyMass extends Model implements Documentable
{
    use HasFactory, DocumentTrait;

    protected $fillable = ['customer_id', 'customer_weight', 'date'];

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function storagePath(): string
    {
        return 'customers/' . $this->customer_id . '/weights_images/' . $this->id;
    }
}
