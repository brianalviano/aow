<?php
use Illuminate\Support\Facades\Schedule;

Schedule::command('promotions:deactivate-expired')->everyMinute();
