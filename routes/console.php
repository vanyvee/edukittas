<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');








Schedule::command('cbt:auto-submit')->everyMinute()->withoutOverlapping();

   Schedule::command('cbt:flush-expired')->hourly()->withoutOverlapping();