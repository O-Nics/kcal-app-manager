<?php

namespace App\Support\Repositories\User;

use App\Features\Auth\Data\UserRegisterData;
use App\Features\User\Data\AddPasswordData;
use App\Features\User\Data\LoginToCustomerData;
use App\Features\User\Data\UpdateMailData;
use App\Features\User\Data\UpdatePasswordData;
use App\Models\Device;
use App\Models\User;
use App\Support\Data\DeviceData;
use App\Support\Repositories\BaseRepository;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UserRepositoryEloquent extends BaseRepository implements UserRepository
{
    protected string $model = User::class;


    /**
     * @throws Throwable
     */
    public function store(UserRegisterData $userRegisterData): User
    {
        $user = new User();
        $user->email = $userRegisterData->email;
        $user->password = Hash::make($userRegisterData->password);
        $user->accept_terms = $userRegisterData->acceptTerms;
        $user->plan = "free";
        $user->saveOrFail();

        $user->customers()->create(
            [
                'is_owner' => true,
                'accept_marketing' => $userRegisterData->acceptMarketing,
            ]
        );

        $user->saveOrFail();


        return $user;
    }

//    public function storeEmpty(): User
//    {
//        $user = new User();
//        $user->password = Hash::make(Str::random(25));
//        $user->saveOrFail();
//        return $user;
//    }
//
//    public function findOrFailByEmail(string $email): User
//    {
//        return $this->query()
//            ->where('email', $email)
//            ->with([
//                'customers',
//            ])
//            ->firstOrFail();
//    }
//
//    public function findByEmail(string $email): ?User
//    {
//        return $this->query()
//            ->where('email', $email)
//            ->with([
//                'customers',
//
//            ])
//            ->first();
//    }
//
//    public function findOrFailById(int $id): User
//    {
//        return $this->query()
//            ->where('id', $id)
//            ->firstOrFail();
//    }
//
//    public function findByApiToken(string $token): ?User {
//        return $this->query()
//            ->where('api_token', $token)
//            ->first();
//    }
//
//    public function findByApiTokenWithEagerLoading(string $token): ?User
//    {
//        return $this->query()
//            ->where('api_token', $token)
//            ->with([
//                'customers',
//            ])
//            ->first();
//    }
//
//    public function changePassword(User $user, PasswordDTO $passwordDTO): User
//    {
//        $user->setAttribute('password', Hash::make($passwordDTO->password));
//        $user->saveOrFail();
//        return $user;
//    }
//
//    public function refreshLoginAt(User $user): void
//    {
//        $user->setAttribute('last_login_at', now());
//        $user->saveOrFail();
//    }
//
//
//    public function createSession(User $user, DeviceInfo $deviceInfo, int $createSessionActionType, NewAccessToken $accessToken, NewAccessToken $refreshToken, string $ip): Session {
//        if(!in_array($createSessionActionType, Session::CREATE_SESSION_ACTIONS)) {
//            throw new Exception("createSessionActionType params must be contains in " . json_encode(Session::CREATE_SESSION_ACTIONS));
//        }
//
//        $device = Device::query()
//            ->where('device_id', $deviceInfo->deviceID)
//            ->where('platform', $deviceInfo->platform)
//            ->where('user_id', $user->id)
//            ->first();
//        if(!$device) {
//         $device = $user->devices()->create(
//                [
//                    'device_id' => $deviceInfo->deviceID,
//                    'platform' => $deviceInfo->platform,
//                    'device_ip' => $ip,
//                    'access_token_id' => $accessToken->accessToken->id,
//                    'refresh_token_id' => $refreshToken->accessToken->id,
//                    'model' => $deviceInfo->model,
//                ]
//            );
//        } else {
//            $device->access_token_id = $accessToken->accessToken->id;
//            $device->refresh_token_id = $refreshToken->accessToken->id;
//            $device->device_ip = $ip;
//            $device->saveOrFail();
//        }
//
//@
//        $devices = Device::query()
//            ->whereHas('sessions', function (Builder $query) use ($user) {
//                $query->where('user_id', $user->id);
//            })
//            ->get();
//
//        /**
//         * todo : import favorites
//         */
//
//        $session = new Session();
//        $session->device()->associate($device);
//        $session->user()->associate($user);
//        $session->saveOrFail();
//        return $session;
//    }
//
//    public function findLastGuestUserByDeviceInfo(DeviceInfo $deviceInfo): ?User
//    {
//        $device = Device::query()
//            ->where('device_id', $deviceInfo->deviceID)
//            ->where('platform', $deviceInfo->platform)
//            ->first();
//        if(!$device) return null;
//        return $this->query()
//            ->whereNull('email')
//            ->whereHas('sessions', function (Builder $query) use ($device) {
//                $query
//                    ->where('device_id', $device->id)
//                    ->orderByDesc('created_at');
//            })
//            ->first();
//    }
//
//    /**
//     * @param User $user
//     * @return void
//     * @throws Throwable
//     */
//    public function delete(User $user): void
//    {
//        $user->delete();
//    }
//
//    public function ratedApp(User $user): void
//    {
//        $user->rated_app_at = now();
//        $user->save();
//    }
//
//
//    public function changeEmail(User $user, UpdateEmailDTO $updateEmailDTO): User
//    {
//        $user->setAttribute('email', $updateEmailDTO->email);
//        $user->saveOrFail();
//        return $user;
//    }
//
//    public function checkPassword(User $user, string $password): bool
//    {
//        return Hash::check($password, $user->password);
//    }
//
//    public function updatePassword(User $user, UpdatePasswordDTO $updatePasswordDTO): User
//    {
//        $user->setAttribute('password', Hash::make($updatePasswordDTO->password));
//        if( $updatePasswordDTO->logoutAllDevices && $updatePasswordDTO->deviceId != null && $updatePasswordDTO->deviceId != "") {
//           $devices = $user->devices()->where('id','!=' ,$updatePasswordDTO->deviceId)->get();
//              foreach ($devices as $device) {
//                  $user->tokens()->where('id', $device->access_token_id)->delete();
//                  $user->tokens()->where('id', $device->refresh_token_id)->delete();
//              }
//        }
//        $user->saveOrFail();
//        return $user;
//    }
//
//    /**
//     * @throws Exception
//     */
//    public function generateOtp(User $user, string $message): OtpToken
//    {
//
//        /** @var OtpToken $otpToken */
//        $otpToken = $user->otpTokens()->create([
//            'token' => Str::random(36),
//        ]);
//
//       $otp = (new Otp)->generate($otpToken->token, "numeric", 6 ,  2);
//        Mail::to($user->email)->send(new VerifyEmailMail($user, $otp->token, $message));
//        return $otpToken;
//    }
//
//    public function checkOtp(User $user, OtpDTO $otpDTO): void
//    {
//        $otp = (new Otp)->validate($otpDTO->token, $otpDTO->otp);
////        dd($otp);
//        if($otp->status){
//            $user->email_verified_at = now();
//            $user->otpTokens()->where('token', $otpDTO->token)->delete();
//            $user->saveOrFail();
//        } else {
//           abort(401, "Le code est invalide ou expiré.");
//        }
//
//    }
//
//    public function changePlan(User $user, ChangePlanUserDTO $changePlanUser): User
//    {
//        $user->setAttribute('plan', $changePlanUser->plan);
//        $user->saveOrFail();
//
//        $device = $user->devices()->where('device_id', $changePlanUser->deviceId)->first();
//        $otherDevices = $user->devices()->where('device_id', '!=', $changePlanUser->deviceId)->get();
//        $customer = $user->customers()->where('is_owner', true)->first();
//        if($device) {
//            $device->customer_id = $customer->id;
//            $device->saveOrFail();
//        }
//        foreach ($otherDevices as $otherDevice) {
//            $user->tokens()->where('id', $otherDevice->access_token_id)->delete();
//            $user->tokens()->where('id', $otherDevice->refresh_token_id)->delete();
//            $otherDevice->delete();
//        }
//        return $user;
//    }
//
//    public function deviceLogout(DeviceLogoutDTO $deviceLogoutDTO, User $user): void
//    {
//        $device = Device::query()
//            ->where('device_id', $deviceLogoutDTO->deviceId)
//            ->where('user_id', $user->id)
//            ->first();
//        if(!$device) {
//            abort(401, "Le device n'existe pas");
//        }
//        $user->tokens()->where('id', $device->access_token_id)->delete();
//        $user->tokens()->where('id', $device->refresh_token_id)->delete();
//        $device->delete();
//    }
//
//    public function loginToCustomer(User $user, LoginToCustomerDTO $loginToCustomerDTO): void
//    {
//        $device = Device::query()
//            ->where('device_id', $loginToCustomerDTO->deviceId)
//            ->where('user_id', $user->id)
//            ->first();
//
//        $device->customer_id = $loginToCustomerDTO->customerId;
//        $device->saveOrFail();
//    }
//
//    public function updateNotification(User $user, string $column, bool $value): void
//    {
//        if($user->$column != $value) {
//            $user->$column = $value;
//            $user->saveOrFail();
//        }
//    }
    public function deviceLogout(DeviceData $deviceData, User $user): void
    {
        $device = Device::query()
            ->where('device_id', $deviceData->deviceId)
            ->where('user_id', $user->id)
            ->first();
        if(!$device) {
            abort(401, "Le device n'existe pas");
        }
        $user->tokens()->where('id', $device->access_token_id)->delete();
        $user->tokens()->where('id', $device->refresh_token_id)->delete();

        $device->delete();
    }

    /**
     * @throws Throwable
     */
    public function loginToCustomer(User $user, LoginToCustomerData $loginToCustomerData): void
    {
        $device = Device::query()
            ->where('device_id', $loginToCustomerData->device->deviceId)
            ->where('user_id', $user->id)
            ->first();

        $device->customer_id = $loginToCustomerData->customerId;
        $device->saveOrFail();
    }

    public function delete(User $user): void
    {
        $email = $user->email;
        $user->delete();
//        Mail::to($email)->send(new DeleteUserAccount("Vous pouvez récupérer votre compte pendant 30 jours en vous connectant à nouveau.", "Suppression de votre compte",
//            "Votre compte a été supprimé avec succès. Vous pouvez le récupérer dans les 30 jours suivant la suppression en vous connectaant à nouveau."));
    }

    public function forceDelete(User $user): void
    {
        $email = $user->email;
        $user->forceDelete();
//        Mail::to($email)->send(new DeleteUserAccount("", "Suppression de votre compte",
//            "Votre compte a été supprimé de manière permanente. Vous ne pourrez pas le récupérer."));
    }

    /**
     * @throws Throwable
     */
    public function recoverAccount(User $user): void
    {
        if(!$user->deleted_at || $user->deleted_at->diffInDays(now()) > 30) {
            abort(403, "Votre compte ne peut pas être récupéré car il a été supprimé définitivement.");
        }
        $user->restore();
        $user->saveOrFail();

//        Mail::to($user->email)->send(new DeleteUserAccount("", "Récupération de votre compte",
//            "Votre compte a été récupéré avec succès. Vous pouvez vous connecter à nouveau."));
    }

    public function checkPassword(User $user, $password): bool
    {
        return Hash::check($password, $user->password);
    }

    /**
     * @throws Throwable
     */
    public function updatePassword(User $user, UpdatePasswordData|AddPasswordData $updatePasswordData): User
    {
        $this->changePassword($user, $updatePasswordData->password);
        if($updatePasswordData instanceof UpdatePasswordData && $updatePasswordData->logoutAllDevices && $updatePasswordData->device->deviceId != null && $updatePasswordData->device->deviceId != "") {
            $devices = $user->devices()->where('id','!=' ,$updatePasswordData->device->deviceId)->get();
            foreach ($devices as $device) {
                /**
                 * @var Device $device
                 */

                $user->tokens()->where('id', $device->access_token_id)->delete();
                $user->tokens()->where('id', $device->refresh_token_id)->delete();
            }
        }
        $user->saveOrFail();
        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()
            ->where('email', $email)
            ->first();
    }

    /**
     * @throws Throwable
     */
    public function changeEmail(User $user, UpdateMailData $updateMailData): User
    {
        $user->setAttribute('email', $updateMailData->email);
        $user->saveOrFail();
        return $user;
    }

    /**
     * @throws Throwable
     */
    public function changePassword(User $user, string $password): User
    {
        $user->setAttribute('password', Hash::make($password));
        $user->saveOrFail();
        return $user;
    }
}
