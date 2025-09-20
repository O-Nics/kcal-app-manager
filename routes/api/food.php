<?php


use App\Features\Customer\Controllers\CustomerController;
use App\Features\Food\Controllers\BrandController;
use App\Features\Food\Controllers\FoodController;
use App\Features\Food\Controllers\IngredientController;
use Illuminate\Support\Facades\Route;

Route::prefix('foods')->group(function () {

    Route::prefix('food/{customer}')->group(function () {
        Route::post('', [FoodController::class, 'addFood']); // à faire
        Route::patch('', [FoodController::class, 'updateFood']); // à faire
        Route::delete('', [FoodController::class, 'deleteFood']); // à faire
        Route::get('', [FoodController::class, 'getFood']); // à faire
    });

    Route::prefix('ingredients/{customer}')->group(function () {
        Route::post('', [IngredientController::class, 'createIngredient']);
        Route::get('{barcode}', [IngredientController::class, 'getIngredientByBarCode']);

        Route::prefix('{ingredient}')->group(function () {
            Route::get('', [IngredientController::class, 'getIngredient']);
            Route::patch('', [IngredientController::class, 'updateIngredient']);
            Route::delete('', [IngredientController::class, 'deleteIngredient']);

            Route::post('favorite', [IngredientController::class, 'handleFavorite']);

            Route::get('favorite', [IngredientController::class, 'getFavoriteIngredients']);
            Route::get('frequent', [IngredientController::class, 'getFrequentIngredients']); // a faire
            Route::get('created', [IngredientController::class, 'getCreatedIngredients']); // a faire
            Route::get('recent', [IngredientController::class, 'getRecentIngredients']); // a faire
        });
    });





    Route::prefix('brands/{customer}')->group(function () {
        Route::get('', [BrandController::class, 'getBrands']);
        Route::post('', [BrandController::class, 'addBrand']);
        Route::delete('{brand}', [BrandController::class, 'deleteBrand']);
    });



});
