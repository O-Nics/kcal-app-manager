<?php

use App\Support\Enums\TokenAbility;
use Illuminate\Support\Facades\Route;

require __DIR__.'/api/auth.php';
require __DIR__.'/api/app.php';

Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::ACCESS_TOKEN->value,])->group(function () {
    require __DIR__ . '/api/user.php';
    require __DIR__ . '/api/customer.php';
});
