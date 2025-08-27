<?php

namespace App\Features\Auth\Controllers;

use App\Features\Auth\Data\CheckPasswordResetData;
use App\Features\Auth\Data\ForgotPasswordData;
use App\Features\Auth\Data\ResetPasswordData;
use App\Features\Auth\Data\RevokeTokenData;
use App\Features\Auth\Data\UserLoginData;
use App\Features\Auth\Data\UserRegisterData;
use App\Features\Auth\Services\TokenService;
use App\Features\User\Resources\UserResource;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Support\Abstract\Controller;
use App\Support\Enums\TokenAbility;
use App\Support\Repositories\Customer\CustomerRepository;
use App\Support\Repositories\PasswordReset\PasswordResetRepository;
use App\Support\Repositories\Session\SessionRepository;
use App\Support\Repositories\User\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    private UserRepository $userRepository;
    private CustomerRepository $customerRepository;
    private SessionRepository $sessionRepository;
    private PasswordResetRepository $passwordResetRepository;
    private TokenService $tokenService;

    public function __construct(
        UserRepository $userRepository,
        CustomerRepository $customerRepository,
        SessionRepository $sessionRepository,
        PasswordResetRepository $passwordResetRepository,
        TokenService $tokenService
    )
    {
        $this->userRepository = $userRepository;
        $this->customerRepository = $customerRepository;
        $this->sessionRepository = $sessionRepository;
        $this->passwordResetRepository = $passwordResetRepository;
        $this->tokenService = $tokenService;
    }

    public function register(UserRegisterData $userRegisterData): JsonResponse
    {

        try {
            $user = $this->userRepository->store($userRegisterData);
            $tokens = $this->tokenService->issueFor(
                $user,
                [TokenAbility::ACCESS_TOKEN->value],
                [TokenAbility::REFRESH_TOKEN->value]
            );

            $session = $this->sessionRepository->createSession($user, $userRegisterData->device, $tokens);
            $customer = $user->customers()->first();
            $customer->last_login_at = now();
            $customer->save();

            return response()->json([
                'success' => true,
                'user' => $user,
                'customer' =>  $customer,
                'device' => $session->device,
                'access_token' => $tokens->accessPlain(),
                'refresh_token' => $tokens->refreshPlain(),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration.',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function login(UserLoginData $userLoginData, Request $request): JsonResponse
    {
        $credentials = [
            'email' => strtolower($userLoginData->email),
            'password' => $userLoginData->password,
        ];
        try {
            if (Auth::attempt($credentials)) {

                /** @var User $user */

                $user = auth()->user();
                $tokens = $this->tokenService->issueFor(
                    $user,
                    [TokenAbility::ACCESS_TOKEN->value],
                    [TokenAbility::REFRESH_TOKEN->value]
                );

                $session = $this->sessionRepository->createSession($user, $userLoginData->device, $tokens);
                $customer = null;
                if($userLoginData->customerId){
                    $customer = $user->customers()
                        ->where('id', $userLoginData->customerId)
                        ->first();

                    $customer->last_login_at = now();
                    $customer->save();
                }

                return response()->json([
                    'success' => true,
                    'user' => $user,
                    'customer' =>  $customer ?: null,
                    'device' => $session->device,
                    'access_token' => $tokens->accessPlain(),
                    'refresh_token' => $tokens->refreshPlain(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "L'email ou le mot de passe est incorrect.",
            ], 401); 

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login.',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        $token = $request->bearerToken();

        $refreshTokenOld = PersonalAccessToken::findToken($token);


        if (!$refreshTokenOld) {
            return response([
                'success' => false,
                'message' => 'Connection expirée ou invalide veuillez vous reconnecter.'
            ], 401);
        }

        if ($refreshTokenOld->expires_at && $refreshTokenOld->expires_at->isPast()) {
            $refreshTokenOld->delete();
            return response([
                'success' => false,
                'message' => 'Connection expirée veuillez vous reconnecter.'
            ], 498);
        }

        $refreshTokenOld->delete();


        $tokens = $this->tokenService->issueFor(
            $refreshTokenOld->tokenable,
            [TokenAbility::ACCESS_TOKEN->value],
            [TokenAbility::REFRESH_TOKEN->value]
        );


        return response(
            [
                'success' => true,
                'message' => "Token généré",
                'access_token' => $tokens->accessPlain(),
                'refresh_token' => $tokens->refreshPlain(),
            ]
        );
    }

    public function revokeDevicesToken(RevokeTokenData $revokeTokenData): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $devices = collect();
        foreach ($revokeTokenData->deviceIds as $deviceId) {
            $device = $user->devices()
                ->where('device_id', $deviceId)
                ->first();

            if($device){
                $devices->push($device);
            }
        }

        if ($devices->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun appareil trouvé pour l\'utilisateur.',
            ], 404);
        }


        foreach ($devices as $device) {
            $user->tokens()->where('id', $device->access_token_id)->delete();
            $user->tokens()->where('id', $device->refresh_token_id)->delete();
            $device->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Appareil' . $devices->count() > 1 ? 's' : ('' . ' supprimé' . $devices->count() > 1 ? 's' : '' . ' avec succès.'),
        ]);

    }

    public function forgotPassword(ForgotPasswordData $forgotPasswordData): JsonResponse {

        $user = $this->userRepository->findByEmail($forgotPasswordData->email);
        if($user) {
            $passwordReset = $this->passwordResetRepository->storePasswordReset($forgotPasswordData);
            Mail::to($user->email)->send(new PasswordResetMail($passwordReset->getUrl(), $user));
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function checkPasswordReset(CheckPasswordResetData $checkPasswordResetData): JsonResponse
    {
        $passwordReset = $this->passwordResetRepository->findByEmailAndToken($checkPasswordResetData->email, $checkPasswordResetData->password);
        if($passwordReset) {
            if($passwordReset->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande de réinitialisation expirée',
                ]);
            } else {
                return response()->json([
                    'success' => true,
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email ou token de la demande erroné',
            ]);
        }
    }

    public function resetPassword(ResetPasswordData $resetPasswordData): JsonResponse {
        $passwordReset = $this->passwordResetRepository->findByEmailAndToken($resetPasswordData->email, $resetPasswordData->token);
        if($passwordReset) {
            if($passwordReset->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Demande de réinitialisation expirée',
                ], 401);
            } else {
                /** @var User $user */
                $user = $this->userRepository->findByEmail($passwordReset->email);
                if($user) {
                    $user = $this->userRepository->changePassword($user, $resetPasswordData->password);
                    $this->passwordResetRepository->remove($passwordReset);

                    return response()->json([
                        'success' => true,
                        'user' => new UserResource($user)
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur introuvable',
                    ], 404);
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Email ou token de la demande erroné',
            ], 404);
        }
    }
}
