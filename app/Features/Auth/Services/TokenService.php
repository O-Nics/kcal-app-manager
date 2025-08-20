<?php
// app/Services/Auth/TokenService.php
namespace App\Features\Auth\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Laravel\Sanctum\NewAccessToken;


final readonly class TokenPair
{
    public function __construct(
        public NewAccessToken $access,
        public NewAccessToken $refresh,
    ) {}

    public function accessPlain(): string
    {
        return $this->access->plainTextToken;
    }

    public function refreshPlain(): string
    {
        return $this->refresh->plainTextToken;
    }

    public function accessId(): int
    {
        return $this->access->accessToken->id;
    }

    public function refreshId(): int
    {
        return $this->refresh->accessToken->id;
    }
}
final class TokenService
{
    /**
     * @param string[] $acAbilities
     * @param string[] $rtAbilities
     */
    public function issueFor(
        User $user,
        array $acAbilities,
        array $rtAbilities,
    ): TokenPair {
        $access  = $user->createToken('access_token',  $acAbilities, Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refresh = $user->createToken('refresh_token', $rtAbilities, Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

        return new TokenPair($access, $refresh);
    }
}
