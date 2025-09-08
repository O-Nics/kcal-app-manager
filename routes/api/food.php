<?php


use App\Features\Customer\Controllers\CustomerController;
use App\Features\Food\Controllers\BrandController;
use App\Features\Food\Controllers\IngredientController;
use Illuminate\Support\Facades\Route;

Route::prefix('foods')->group(function () {


    Route::prefix('ingredients')->group(function () {
        Route::prefix('{customer}')->group(function () {

            Route::post('', [IngredientController::class, 'createIngredient']);

            Route::prefix('{ingredient}')->group(function () {
                Route::get('', [IngredientController::class, 'getIngredientById']);
                Route::patch('', [IngredientController::class, 'updateIngredient']);
                Route::delete('', [IngredientController::class, 'deleteIngredient']);
            });
        });

        Route::get('get-ingredient-by-barcode', [IngredientController::class, 'getIngredientById']);
    });

    Route::prefix('brands')->group(function () {
        Route::prefix('{customer}')->group(function () {
            Route::post('', [BrandController::class, 'addBrand']);
            Route::delete('{brand}', [BrandController::class, 'deleteBrand']);
        });
    });



});
