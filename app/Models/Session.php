<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Session
 *
 * @property int $id
 * @property int $user_id
 * @property int $device_id
 * @property-read User $user
 * @property-read Device $device
 */
class Session extends Model
{
    public const CREATE_SESSION_ACTION_TYPE_LOGIN_CREDENTIALS = 0;

    public const CREATE_SESSION_ACTION_REGISTER_REAL_USER = 1;

    public const CREATE_SESSION_ACTION_REGISTER_GUEST = 2;

    public const CREATE_SESSION_ACTIONS = [
        self::CREATE_SESSION_ACTION_TYPE_LOGIN_CREDENTIALS,
        self::CREATE_SESSION_ACTION_REGISTER_REAL_USER,
        self::CREATE_SESSION_ACTION_REGISTER_GUEST,
    ];

    public function user():BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function device():BelongsTo {
        return $this->belongsTo(Device::class);
    }
}
