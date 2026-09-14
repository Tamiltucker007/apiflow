<?php

use App\Jobs\AggregateUsageJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping() here guards the schedule itself; the job's own
// WithoutOverlapping middleware guards against a manual `usage:aggregate`
// dispatch colliding with the scheduled run — belt and suspenders.
Schedule::job(new AggregateUsageJob)->hourly()->withoutOverlapping();

// Runs after the last aggregation pass of the day so any subscription whose
// period ended today bills against fully-aggregated daily_usage totals.
Schedule::command('billing:generate-invoices')->dailyAt('01:00');
