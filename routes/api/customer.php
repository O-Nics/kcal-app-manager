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
        Route::patch('update-objective', [CustomerController::class, 'updateGoal']);
        Route::patch('update-activity', [CustomerController::class, 'updateActivity']);
        Route::patch('update-start-weight', [CustomerController::class, 'updateStartWeight']);
        Route::patch('update-wanted-weight', [CustomerController::class, 'updateWantedWeight']);
        Route::patch('update-hebdo', [CustomerController::class, 'updateHebdo']);
        Route::patch('update-kcal', [CustomerController::class, 'updateKcal']);
        Route::patch('update-nutrition', [CustomerController::class, 'updateNutrition']);
        Route::patch('update-steps', [CustomerController::class, 'updateSteps']);
        Route::patch('update-volume-water-by-each', [CustomerController::class, 'updateVolumeWaterByEach']);
        Route::patch('update-volume-water', [CustomerController::class, 'updateVolumeWater']);


    });

});
