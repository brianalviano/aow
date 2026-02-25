<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:cancel-unpaid-orders')->everyMinute();
