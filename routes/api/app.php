<?php


use App\Features\App\Controllers\AppController;
use App\Features\Auth\Controllers\AuthController;
use App\Support\Enums\TokenAbility;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->group(function () {
    Route::get('initial-data', [AppController::class, 'initialData']);
    Route::get('availability', [AppController::class, 'availability']);
});
