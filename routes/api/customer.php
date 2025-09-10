<?php


use App\Features\Customer\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->group(function () {


    Route::get('', [CustomerController::class, 'customers']);


    Route::prefix('{customer}')->group(function () {

        Route::get('', [CustomerController::class, 'customer']);
        Route::patch('customer', [CustomerController::class, 'updateCustomer']);
        Route::delete('delete', [CustomerController::class, 'delete']);

        Route::post('program', [CustomerController::class, 'createProgram']);

        Route::post('weight', [CustomerController::class, 'addWeight']);

        Route::prefix('password')->group(function () {
            Route::get('', [CustomerController::class, 'checkPassword']);
            Route::post('', [CustomerController::class, 'handlePassword']);
        });

        Route::prefix('water')->group(function () {
            Route::post('', [CustomerController::class, 'addWater']);
            Route::delete('', [CustomerController::class, 'delWater']);
        });
        

        Route::patch('update-notification', [CustomerController::class, 'updateNotification']);







    });

});
