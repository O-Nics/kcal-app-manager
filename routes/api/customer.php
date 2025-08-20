<?php


use App\Features\Customer\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer/{customer}')->group(function () {
    Route::get('', [CustomerController::class, 'customer']);
    Route::post('create-program', [CustomerController::class, 'createProgram']);

});
