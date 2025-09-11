<?php


use App\Features\Auth\Controllers\AuthController;
use App\Features\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::get('', [UserController::class, 'user']);
    Route::delete('', [UserController::class, 'delete']);

    Route::get('devices', [UserController::class, 'devices']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::post('customer', [UserController::class, 'loginToCustomer']);
    Route::patch('email', [UserController::class, 'updateEmail']);

    Route::prefix('password')->group(function () {

        Route::patch('', [UserController::class, 'updatePassword']);
        Route::post('', [UserController::class, 'addPassword']);

    });

    Route::post('revoke-token', [AuthController::class, 'revokeDevicesToken']);

});
