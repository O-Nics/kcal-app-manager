<?php


use App\Features\Customer\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer')->group(function () {

    Route::post('create-program', [CustomerController::class, 'createProgram']);


});
