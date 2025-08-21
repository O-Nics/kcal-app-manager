<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;


/** * Class PasswordReset
 * @package App\Models
 *
 * @property string $email
 * @property string $token
 * @property Carbon $created_at
 */
class PasswordReset extends Model
{
    protected $table = "password_resets";

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public const int EXPIRATION_TIME = 60;

    public function getUrl():string {
        return env('CLIENT_APP_BASE_URL')."/reset-password?email=$this->email&token=$this->token";
    }

    public function isExpired(): bool {
        return $this->created_at->addMinutes(PasswordReset::EXPIRATION_TIME)->lessThan(now());
    }
}
