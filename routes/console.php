<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule FS.com catalog synchronization (daily at 2 AM)
Schedule::command('fscom:sync')->dailyAt('02:00')->onSuccess(function () {
    info('FS.com catalog synchronization completed successfully');
})->onFailure(function () {
    error('FS.com catalog synchronization failed');
});
