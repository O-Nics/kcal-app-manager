<?php


use App\Features\Customer\Controllers\CustomerController;
use App\Features\Food\Controllers\BrandController;
use App\Features\Food\Controllers\IngredientController;
use Illuminate\Support\Facades\Route;

Route::prefix('foods')->group(function () {


    Route::prefix('ingredients')->group(function () {
        Route::prefix('{customer}')->group(function () {

            Route::post('', [IngredientController::class, 'createIngredient']);
            Route::get('{barcode}', [IngredientController::class, 'getIngredientByBarCode']);

            Route::prefix('{ingredient}')->group(function () {
                Route::get('', [IngredientController::class, 'getIngredient']);
                Route::patch('', [IngredientController::class, 'updateIngredient']);
                Route::delete('', [IngredientController::class, 'deleteIngredient']);

                Route::post('favorite', [IngredientController::class, 'handleFavorite']);
            });

        });
    });

    Route::prefix('brands')->group(function () {
        Route::prefix('{customer}')->group(function () {
            Route::get('', [BrandController::class, 'getBrands']);
            Route::post('', [BrandController::class, 'addBrand']);
            Route::delete('{brand}', [BrandController::class, 'deleteBrand']);
        });
    });



});
