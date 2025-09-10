<?php


use App\Features\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'user']);
    Route::get('/devices', [UserController::class, 'devices']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/login-to-customer', [UserController::class, 'loginToCustomer']);
    Route::delete('/delete', [UserController::class, 'delete']);
    Route::patch('/update-email', [UserController::class, 'updateEmail']);
    Route::patch('/update-password', [UserController::class, 'updatePassword']);
    Route::post('/add-password', [UserController::class, 'addPassword']);
    Route::post('/revoke-token', [AuthController::class, 'revokeDevicesToken']);

});
