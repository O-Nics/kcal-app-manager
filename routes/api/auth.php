<?php


use App\Features\Auth\Controllers\AuthController;
use App\Support\Enums\TokenAbility;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('check-password-reset', [AuthController::class, 'checkPasswordReset']);
    Route::put('reset-password', [AuthController::class, 'resetPassword']);



    Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::REFRESH_TOKEN->value, ])->group(function () {
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('revoke-token', [AuthController::class, 'revokeDevicesToken']);

    });

});
