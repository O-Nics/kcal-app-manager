<?php


use App\Features\Customer\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->group(function () {


    Route::get('', [CustomerController::class, 'customers']);


    Route::prefix('{customer}')->group(function () {

        Route::get('', [CustomerController::class, 'customer']);
        Route::patch('', [CustomerController::class, 'updateCustomer']);
        Route::delete('', [CustomerController::class, 'delete']);

        Route::prefix('program')->group(function () {
            Route::post('', [CustomerController::class, 'createProgram']);
        });

        Route::prefix('weight')->group(function () {

            Route::post('', [CustomerController::class, 'addWeight']);

            Route::prefix('{weight}')->group(function () { // a faire
                Route::get('', [CustomerController::class, 'getWeight']);
                Route::patch('', [CustomerController::class, 'updateWeight']);
                Route::delete('', [CustomerController::class, 'deleteWeight']);
            });

        });

        Route::prefix('password')->group(function () {

            Route::get('', [CustomerController::class, 'checkPassword']);
            Route::patch('', [CustomerController::class, 'handlePassword']);

        });

        Route::prefix('water')->group(function () {

            Route::get('', [CustomerController::class, 'getWater']); // à faire
            Route::post('', [CustomerController::class, 'addWater']);
            Route::delete('', [CustomerController::class, 'delWater']);

        });


    });

});
