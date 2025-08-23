<?php


use App\Features\Customer\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->group(function () {


    Route::get('', [CustomerController::class, 'customers']);


    Route::prefix('{customer}')->group(function () {

        Route::get('', [CustomerController::class, 'customer']);
        Route::post('create-program', [CustomerController::class, 'createProgram']);
        Route::patch('update-notification', [CustomerController::class, 'updateNotification']);
        Route::delete('delete', [CustomerController::class, 'delete']);
        Route::post('handle-password', [CustomerController::class, 'password']);
        Route::get('check-password', [CustomerController::class, 'checkPassword']);
        Route::post('weight', [CustomerController::class, 'addWeight']);


    });

});
