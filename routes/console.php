<?php

use App\Jobs\GenerateKpiNarrative;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rewrite the executive dashboard's AI commentary each morning, so the
// dashboard stays "purely updated by AI" without per-view model calls.
Schedule::job(new GenerateKpiNarrative)->dailyAt('06:00');
