<?php

use App\Jobs\SyncDeviceIntegrationsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here you may define all of your scheduled tasks. The scheduler runs
| every minute and executes jobs as needed.
|
*/

Schedule::job(new SyncDeviceIntegrationsJob)->everyMinute();
