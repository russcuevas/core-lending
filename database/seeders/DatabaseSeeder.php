<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\HostVaultLedger;
use App\Models\SystemSetting;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Host Vault Capital Fund (Starting at ₱0.00)
        HostVaultLedger::create([
            'type' => 'in',
            'category' => 'initial_capital',
            'amount' => 0.00,
            'description' => 'Initial Company Capital Fund',
            'vault_balance_after' => 0.00,
            'created_at' => Carbon::now(),
        ]);

        // 2. Superadmin / Host Account
        User::create([
            'name' => 'Host Superadmin',
            'email' => 'host@lending.com',
            'phone_number' => '09990000001',
            'password' => Hash::make('password123'),
            'role' => 'host',
            'address' => 'Central Operations, Main Headquarters',
            'status' => 'active',
        ]);

        // 3. Default Global System Settings & Rates
        SystemSetting::set(
            'loan_interest_rate_percent',
            '10.00',
            'float',
            'loan',
            'Default Loan Interest Rate (%)',
            'Standard global interest percentage applied to newly encoded loan applications.'
        );

        SystemSetting::set(
            'loan_term_days',
            '60',
            'int',
            'loan',
            'Default Loan Term (Days)',
            'Standard total payment cycle days for new loan applications.'
        );

        SystemSetting::set(
            'savings_interest_rate_percent',
            '10.00',
            'float',
            'savings',
            'Client Savings Return Rate (%)',
            'Guaranteed return percentage earned by clients for locked savings deposits.'
        );

        SystemSetting::set(
            'savings_lock_in_days',
            '60',
            'int',
            'savings',
            'Savings Lock-in Period (Days)',
            'Number of days client savings funds remain locked to earn daily interest payouts.'
        );

        SystemSetting::set(
            'collector_loan_commission_fixed',
            '300.00',
            'float',
            'collector',
            'Collector Commission per Fully-Paid Loan (₱)',
            'Incentive credited to collector wallet upon client completing all loan schedules.'
        );

        SystemSetting::set(
            'collector_savings_commission_percent',
            '5.00',
            'float',
            'collector',
            'Collector Commission on Savings (%)',
            'Instant commission percentage credited to collector balance when client deposits to savings.'
        );
    }
}
