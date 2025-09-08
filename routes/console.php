<?php

use App\Support\Jobs\Intern\GetBrandsFromOpenFoodFactJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::job(new GetBrandsFromOpenFoodFactJob());
Schedule::job(new \App\Support\Jobs\Intern\GetIngredientsFormOpenFoodFactJob());
