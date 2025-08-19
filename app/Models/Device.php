<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * @property int $id
 * @property int $user_id
 * @property string $device_id
 * @property string $platform
 * @property string $device_ip
 * @property int $access_token_id
 * @property int $refresh_token_id
 * @property string $model
 * @property string $customer_id
 */
class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'platform',
        'device_ip',
        'access_token_id',
        'refresh_token_id',
        'model',
        'customer_id'
    ];

    public function sessions():HasMany {
        return $this->hasMany(Session::class);
    }

    public function users():HasMany {
        return $this->hasMany(User::class);
    }

    public function lastSessionForUser(User $user):HasOne {
        return $this->hasOne(Session::class)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(1);
    }

    public function lastSession():HasOne {
        return $this->hasOne(Session::class)
            ->orderByDesc('created_at')
            ->limit(1);
    }
}
