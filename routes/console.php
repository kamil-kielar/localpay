<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('lokalpay:send-rent-reminders')
    ->dailyAt('08:00')
    ->timezone('Europe/Warsaw')
    ->withoutOverlapping()
    ->onOneServer();
