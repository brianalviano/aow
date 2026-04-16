<?php

use App\DTOs\Setting\OrderSettingsDTO;
use App\Services\OrderService;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cancel-unpaid-orders')->everyMinute();
Schedule::command('app:generate-daily-summary')->dailyAt('23:55');
Schedule::call(fn() => app(OrderService::class)->autoCompleteArrivedOrders())->hourly();

// We use the `order_cutoff_time` from settings as the time to run the quota check.
try {
    $cutoffTime = OrderSettingsDTO::load()->orderCutoffTime;
} catch (\Throwable $e) {
    // Fallback for when the database is not available (e.g., during composer dump-autoload or migrations)
    $cutoffTime = '15:00';
}
Schedule::command('app:check-po-quotas')->dailyAt($cutoffTime);
