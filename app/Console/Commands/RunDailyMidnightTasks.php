<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunDailyMidnightTasks extends Command
{
    protected $signature = 'app:run-midnight-tasks';
    protected $description = 'Runs all daily 12:00 MN midnight tasks: Loan Auto-Deductions and Savings Daily Interest.';

    public function handle()
    {
        $this->info("🌙 Running 12:00 MN Midnight Automated Tasks...");

        // 1. Auto-deduct daily loan payments
        $this->info("1/2: Processing Daily Loan Auto-Deductions...");
        Artisan::call('loan:auto-deduct-daily-payments', [], $this->output);

        // 2. Credit daily savings interest
        $this->info("2/2: Processing Daily Savings Interest...");
        Artisan::call('savings:credit-daily-interest', [], $this->output);

        $this->info("✓ All 12:00 MN Midnight tasks completed successfully.");
        return 0;
    }
}
