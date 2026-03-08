<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cancel-unpaid-orders')->everyMinute();
Schedule::command('app:generate-daily-summary')->dailyAt('23:55');

// We use the `order_cutoff_time` from settings as the time to run the quota check.
try {
    $cutoffTime = \App\DTOs\Setting\OrderSettingsDTO::load()->orderCutoffTime;
} catch (\Throwable $e) {
    // Fallback for when the database is not available (e.g., during composer dump-autoload or migrations)
    $cutoffTime = '15:00';
}
Schedule::command('app:check-po-quotas')->dailyAt($cutoffTime);
