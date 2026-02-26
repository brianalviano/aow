<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cancel-unpaid-orders')->everyMinute();
Schedule::command('app:generate-daily-summary')->dailyAt('23:55');
