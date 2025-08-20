<?php


use App\Features\App\Controllers\AppController;
use App\Features\Auth\Controllers\AuthController;
use App\Support\Enums\TokenAbility;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::get('load-initial-data', [AppController::class, 'loadInitialData']);
    Route::get('check-availability', [AppController::class, 'checkAvailability']);
});
