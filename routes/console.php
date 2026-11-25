<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console commands
|--------------------------------------------------------------------------
*/
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Only ONE cron line is needed on the server:
| * * * * * cd /var/www/myapp && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
*/

// 1. Queue worker: processes waiting SMS jobs (the "database" connection).
//    Starts every minute, empties the queue, then exits before the next tick.
Schedule::command('queue:work database --sleep=2 --max-time=55')
    ->everyMinute()
    ->between('07:00', '23:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping(5)
    ->runInBackground();
// 2. Month-end farmer payouts (M-Pesa B2C).
Schedule::command('payouts:disburse')
    ->lastDayOfMonth('09:00')
    ->timezone('Africa/Nairobi')
    ->withoutOverlapping()
    ->runInBackground();

// 3. Optional: daily task at 08:00. Replace with your real command or delete.
// Schedule::command('reports:daily')
//     ->dailyAt('08:00')
//     ->timezone('Africa/Nairobi')
//     ->withoutOverlapping();

// 4. Optional: weekly task on Monday at 09:00. Replace or delete.
// Schedule::command('reports:weekly')
//     ->weeklyOn(1, '09:00')
//     ->timezone('Africa/Nairobi')
//     ->withoutOverlapping();