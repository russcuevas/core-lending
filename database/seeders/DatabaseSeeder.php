<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Collector;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\Expense;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Host Vault Capital
        HostVaultLedger::create([
            'type' => 'in',
            'category' => 'initial_capital',
            'amount' => 1000000.00,
            'description' => 'Initial Company Capital Fund',
            'vault_balance_after' => 1000000.00,
            'created_at' => Carbon::now()->subDays(30),
        ]);

        // 2. Superadmin / Host
        $host = User::create([
            'name' => 'Host Superadmin',
            'email' => 'host@lending.com',
            'phone_number' => '09990000001',
            'password' => Hash::make('password123'),
            'role' => 'host',
            'address' => 'Central Operations, Main Headquarters',
            'status' => 'active',
        ]);

        // 3. Admin Encoder
        $encoder = User::create([
            'name' => 'Admin Juan Encoder',
            'email' => 'encoder@lending.com',
            'phone_number' => '09990000002',
            'password' => Hash::make('password123'),
            'role' => 'admin_encoder',
            'address' => 'Encoding Unit, Branch 1',
            'status' => 'active',
        ]);

        // 4. Admin Releasing Officer
        $releasing = User::create([
            'name' => 'Admin Maria Releasing',
            'email' => 'releasing@lending.com',
            'phone_number' => '09990000003',
            'password' => Hash::make('password123'),
            'role' => 'admin_releasing',
            'address' => 'Disbursement Unit, Branch 1',
            'status' => 'active',
        ]);

        // 5. Collector 1
        $collectorUser1 = User::create([
            'name' => 'Pedro Penduko (Collector)',
            'email' => 'collector@lending.com',
            'phone_number' => '09181234567',
            'password' => Hash::make('password123'),
            'pin_code' => '1234',
            'role' => 'collector',
            'address' => 'Zone 4, Brgy. San Antonio, Pasig City',
            'status' => 'active',
        ]);

        $collector1 = Collector::create([
            'user_id' => $collectorUser1->id,
            'assigned_area' => 'District 1 - San Antonio / Kapitolyo',
            'commission_balance' => 800.00, // sample balance from previous commissions
            'total_earned_commission' => 2400.00,
        ]);

        // 6. Collector 2 (Pending approval by host demo)
        $collectorUser2 = User::create([
            'name' => 'Ramon Magsaysay (New Collector)',
            'email' => 'collector2@lending.com',
            'phone_number' => '09189998877',
            'password' => Hash::make('password123'),
            'pin_code' => '1234',
            'role' => 'collector',
            'address' => 'Brgy. Ugong, Pasig City',
            'status' => 'pending',
        ]);

        $collector2 = Collector::create([
            'user_id' => $collectorUser2->id,
            'assigned_area' => 'District 2 - Ugong / Maybunga',
            'commission_balance' => 0.00,
            'total_earned_commission' => 0.00,
        ]);

        // 7. Client 1 (Active loan, paying daily)
        $clientUser1 = User::create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'phone_number' => '09171234567',
            'password' => Hash::make('1234'), // same as pin
            'pin_code' => '1234',
            'role' => 'client',
            'address' => 'Blk 12 Lot 4, Sunflower St., Pasig City',
            'status' => 'active',
        ]);

        $token1 = 'CLIENT-QR-' . strtoupper(substr(md5($clientUser1->id . '09171234567'), 0, 10));
        $client1 = Client::create([
            'user_id' => $clientUser1->id,
            'collector_id' => $collector1->id,
            'qr_code_token' => $token1,
            'wallet_balance' => 2500.00,
            'status' => 'active',
            'last_payment_date' => Carbon::now()->format('Y-m-d'),
            'consecutive_missed_days' => 0,
        ]);

        // Create Loan for Client 1 (₱20,000 + 10% = ₱22,000 / 60 days = ₱366.67/day)
        $principal1 = 20000.00;
        $interestRate1 = 10.00;
        $totalPayable1 = 22000.00;
        $dailyDue1 = round($totalPayable1 / 60, 2);

        $loan1 = Loan::create([
            'client_id' => $client1->id,
            'collector_id' => $collector1->id,
            'principal_amount' => $principal1,
            'interest_rate_percent' => $interestRate1,
            'total_payable' => $totalPayable1,
            'daily_installment' => $dailyDue1,
            'term_days' => 60,
            'remaining_balance' => $totalPayable1 - ($dailyDue1 * 5),
            'total_paid' => $dailyDue1 * 5,
            'status' => 'active',
            'release_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'release_note' => 'Approved for store expansion.',
            'encoder_id' => $encoder->id,
            'releasing_officer_id' => $releasing->id,
            'host_approved_by' => $host->id,
            'host_approved_at' => Carbon::now()->subDays(6),
        ]);

        $client1->update(['current_loan_id' => $loan1->id]);

        // Generate 60 days schedule for Loan 1
        for ($day = 1; $day <= 60; $day++) {
            $dueDate = Carbon::now()->subDays(5)->addDays($day - 1)->format('Y-m-d');
            $isPaid = $day <= 5;
            LoanSchedule::create([
                'loan_id' => $loan1->id,
                'day_number' => $day,
                'due_date' => $dueDate,
                'expected_amount' => $dailyDue1,
                'paid_amount' => $isPaid ? $dailyDue1 : 0.00,
                'status' => $isPaid ? 'paid' : 'unpaid',
                'paid_at' => $isPaid ? Carbon::now()->subDays(5 - $day + 1) : null,
            ]);

            if ($isPaid) {
                LoanPayment::create([
                    'loan_id' => $loan1->id,
                    'client_id' => $client1->id,
                    'collector_id' => $collector1->id,
                    'amount_paid' => $dailyDue1,
                    'client_pin_verified' => true,
                    'payment_date' => $dueDate,
                    'notes' => "Day {$day} Daily Collection by {$collectorUser1->name}",
                    'client_remaining_balance_after' => $totalPayable1 - ($dailyDue1 * $day),
                ]);
            }
        }

        // 8. Client 2 (With Savings Fund: ₱10,000, 10% 60 days lock-in, daily interest ₱16.67)
        $clientUser2 = User::create([
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'phone_number' => '09177654321',
            'password' => Hash::make('1234'),
            'pin_code' => '1234',
            'role' => 'client',
            'address' => 'Unit 301, Rose Tower, Kapitolyo, Pasig City',
            'status' => 'active',
        ]);

        $token2 = 'CLIENT-QR-' . strtoupper(substr(md5($clientUser2->id . '09177654321'), 0, 10));
        $client2 = Client::create([
            'user_id' => $clientUser2->id,
            'collector_id' => $collector1->id,
            'qr_code_token' => $token2,
            'wallet_balance' => 3800.00,
            'status' => 'active',
            'last_payment_date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'consecutive_missed_days' => 0,
        ]);

        // Savings Account for Client 2
        $savingsDeposit = 10000.00;
        $dailyInterest = round(($savingsDeposit * 0.10) / 60, 4); // 16.6667
        $commission5Percent = $savingsDeposit * 0.05; // 500.00

        SavingsAccount::create([
            'client_id' => $client2->id,
            'collector_id' => $collector1->id,
            'deposit_amount' => $savingsDeposit,
            'interest_rate_percent' => 10.00,
            'lock_in_days' => 60,
            'daily_interest_amount' => $dailyInterest,
            'total_expected_interest' => 1000.00,
            'accumulated_interest_paid' => round($dailyInterest * 10, 2),
            'days_credited' => 10,
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'maturity_date' => Carbon::now()->subDays(10)->addDays(60)->format('Y-m-d'),
            'status' => 'active',
            'collector_commission_amount' => $commission5Percent,
            'collector_commission_credited' => true,
        ]);

        // 9. Client 3 (New Application waiting for Releasing & Host Approval)
        $clientUser3 = User::create([
            'name' => 'Roberto Gomez',
            'email' => 'roberto@example.com',
            'phone_number' => '09171112233',
            'password' => Hash::make('1234'),
            'pin_code' => '1234',
            'role' => 'client',
            'address' => '77 M. Concepcion Ave, San Joaquin, Pasig City',
            'status' => 'pending_host_approval',
        ]);

        $token3 = 'CLIENT-QR-' . strtoupper(substr(md5($clientUser3->id . '09171112233'), 0, 10));
        $client3 = Client::create([
            'user_id' => $clientUser3->id,
            'collector_id' => $collector1->id,
            'qr_code_token' => $token3,
            'wallet_balance' => 0.00,
            'status' => 'pending_host_approval',
            'consecutive_missed_days' => 0,
        ]);

        $loan3 = Loan::create([
            'client_id' => $client3->id,
            'collector_id' => $collector1->id,
            'principal_amount' => 15000.00,
            'interest_rate_percent' => 10.00,
            'total_payable' => 16500.00,
            'daily_installment' => round(16500.00 / 60, 2),
            'term_days' => 60,
            'remaining_balance' => 16500.00,
            'total_paid' => 0.00,
            'status' => 'pending_host_approval',
            'encoder_id' => $encoder->id,
        ]);

        for ($day = 1; $day <= 60; $day++) {
            LoanSchedule::create([
                'loan_id' => $loan3->id,
                'day_number' => $day,
                'due_date' => Carbon::now()->addDays($day)->format('Y-m-d'),
                'expected_amount' => round(16500.00 / 60, 2),
                'paid_amount' => 0.00,
                'status' => 'unpaid',
            ]);
        }

        // 10. Sample Pending Cash-In / Cash-Out transactions
        WalletTransaction::create([
            'user_id' => $clientUser1->id,
            'type' => 'cash_in',
            'amount' => 3000.00,
            'status' => 'pending_releasing_review',
            'releasing_notes' => 'Client requested cash in via field collection.',
        ]);

        WalletTransaction::create([
            'user_id' => $clientUser2->id,
            'type' => 'cash_out',
            'amount' => 1000.00,
            'status' => 'pending_host_approval',
            'releasing_officer_id' => $releasing->id,
            'releasing_notes' => 'Verified client valid ID. Recommended release tomorrow.',
            'releasing_scheduled_date' => Carbon::now()->addDay()->format('Y-m-d'),
        ]);

        WalletTransaction::create([
            'user_id' => $collectorUser1->id,
            'type' => 'collector_cashout',
            'amount' => 500.00,
            'status' => 'pending_releasing_review',
            'releasing_notes' => 'Collector commission payout request.',
        ]);

        // 11. Sample Expenses
        Expense::create([
            'user_id' => $encoder->id,
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'particulars' => 'Office Printing Paper and QR Laminates',
            'amount' => 850.00,
            'category' => 'Office Supplies',
        ]);

        Expense::create([
            'user_id' => $encoder->id,
            'date' => Carbon::now()->subDay()->format('Y-m-d'),
            'particulars' => 'Motorcycle Fuel Allowance for Field Verification',
            'amount' => 500.00,
            'category' => 'Transportation',
        ]);

        // 12. Sample System Notifications
        SystemNotification::create([
            'user_id' => $host->id,
            'target_role' => 'host',
            'title' => 'New Client Registration Pending Approval',
            'message' => 'Admin Encoder created new client application for Roberto Gomez (₱15,000.00).',
            'type' => 'approval_needed',
            'link_url' => '/host/approvals',
        ]);

        SystemNotification::create([
            'user_id' => $releasing->id,
            'target_role' => 'admin_releasing',
            'title' => 'New Cash In Request (₱3,000.00)',
            'message' => 'Client Juan Dela Cruz submitted a Cash-In request.',
            'type' => 'request_alert',
            'link_url' => '/admin/releasing/requests',
        ]);

        // 13. Default Global System Settings
        \App\Models\SystemSetting::set('loan_interest_rate_percent', '10.00', 'float', 'loan', 'Default Loan Interest Rate (%)', 'Standard global interest percentage applied to newly encoded loan applications.');
        \App\Models\SystemSetting::set('loan_term_days', '60', 'int', 'loan', 'Default Loan Term (Days)', 'Standard total payment cycle days for new loan applications.');
        \App\Models\SystemSetting::set('savings_interest_rate_percent', '10.00', 'float', 'savings', 'Client Savings Return Rate (%)', 'Guaranteed return percentage earned by clients for locked savings deposits.');
        \App\Models\SystemSetting::set('savings_lock_in_days', '60', 'int', 'savings', 'Savings Lock-in Period (Days)', 'Number of days client savings funds remain locked to earn daily interest payouts.');
        \App\Models\SystemSetting::set('collector_loan_commission_fixed', '300.00', 'float', 'collector', 'Collector Commission per Fully-Paid Loan (₱)', 'Incentive credited to collector wallet upon client completing all loan schedules.');
        \App\Models\SystemSetting::set('collector_savings_commission_percent', '5.00', 'float', 'collector', 'Collector Commission on Savings (%)', 'Instant commission percentage credited to collector balance when client deposits to savings.');
    }
}
