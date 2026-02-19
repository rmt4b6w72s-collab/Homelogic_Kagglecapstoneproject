<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notifications to be generated every hour
Schedule::command('notifications:generate')->hourly();

// Reminders
Schedule::command('reminders:generate')->everyThirtyMinutes();
Schedule::command('reminders:dispatch')->everyFiveMinutes();

// Medications - Mark missed medications:
// 1. Real-time: Every 5 minutes to catch missed windows shortly after they close
Schedule::command('medications:mark-missed')->everyFiveMinutes();
// 2. End-of-day: Daily at 11:55 PM to catch any missed doses from the day
Schedule::command('medications:mark-missed --end-of-day')->dailyAt('23:55');

// Medications - Notify caregivers when administration windows open:
// Run every 5 minutes to catch windows as they open
Schedule::command('medications:notify-window-opening')->everyFiveMinutes();
