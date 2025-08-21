<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return new \App\Mail\TestEmail()->render();
});
