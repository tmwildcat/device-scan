<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('legal:verify-integrity --actor=scheduler')->dailyAt('02:40')->withoutOverlapping();
Schedule::command('legal:publish-scheduled')->everyMinute()->withoutOverlapping();
