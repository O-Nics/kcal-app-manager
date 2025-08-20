<?php


use Illuminate\Support\Facades\Route;

Route::prefix('user')->group(function () {
    Route::get('/', [\App\Features\User\Controllers\UserController::class, 'user']);

});
