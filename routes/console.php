<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('water:finalize-expired')->everyMinute();
