<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\WalletTransaction;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use App\Models\SystemSetting;
use Carbon\Carbon;

class AutoDeductDailyLoanPayments extends Command
{
    protected $signature = 'loan:auto-deduct-daily-payments';
    protected $description = 'Automatically deducts daily loan installments from client wallet balances at 12:00 MN if balance is sufficient.';

    public function handle()
    {
        $today = Carbon::today();
        $todayDateStr = $today->format('Y-m-d');
        $this->info("Starting 12:00 MN Automated Wallet Deduction for {$todayDateStr}...");

        $activeLoans = Loan::where('status', 'active')
            ->with(['client.user', 'collector.user', 'schedules'])
            ->get();

        $processedCount = 0;
        $skippedCount = 0;
        $totalDeducted = 0.00;

        foreach ($activeLoans as $loan) {
            $client = $loan->client;
            if (!$client || !$client->user) {
                continue;
            }

            // Sync past due days first
            $loan->syncMissedDaysAndExtensions();
            $loan->refresh();

            // Find next unpaid schedule
            $unpaidSchedule = $loan->schedules()->where('status', '!=', 'paid')->first();
            if (!$unpaidSchedule) {
                continue;
            }

            // Determine daily payable amount
            $insuranceDaily = (float)($loan->insurance_premium_daily > 0 ? $loan->insurance_premium_daily : SystemSetting::get('loan_insurance_premium_daily', 5.00));
            $loanDaily = (float)($loan->loan_premium_daily > 0 ? $loan->loan_premium_daily : $loan->daily_installment);
            $dailyDue = $loanDaily + $insuranceDaily;

            if ($dailyDue <= 0) {
                $dailyDue = (float)$unpaidSchedule->expected_amount;
            }

            $walletBalance = (float)$client->wallet_balance;

            // Strict rule: Only deduct if wallet balance is >= dailyDue
            if ($walletBalance < $dailyDue) {
                $this->line("Skipping {$client->user->name} - insufficient wallet balance (Wallet: ₱" . number_format($walletBalance, 2) . ", Due: ₱" . number_format($dailyDue, 2) . ")");
                $skippedCount++;
                continue;
            }

            // Execute deduction
            $client->decrement('wallet_balance', $dailyDue);
            $client->refresh();

            $insurancePart = min($dailyDue, $insuranceDaily);
            $loanPart = max(0, $dailyDue - $insurancePart);

            // 1. Create completed Wallet Transaction
            $walletTx = WalletTransaction::create([
                'user_id' => $client->user_id,
                'type' => 'loan_payment',
                'amount' => $dailyDue,
                'status' => 'completed',
                'pin_verified' => true,
                'user_balance_after' => $client->wallet_balance,
                'releasing_notes' => "Automated 12:00 MN loan payment deduction from wallet for Loan #{$loan->id} (Day {$unpaidSchedule->day_number}).",
            ]);

            // 2. Update loan schedules and balances
            $newRemainingBalance = max(0, $loan->remaining_balance - $loanPart);
            $newTotalPaid = $loan->total_paid + $loanPart;
            $isFullyPaid = ($newRemainingBalance <= 0);

            $remainingToDistribute = $loanPart;
            $unpaidSchedules = $loan->schedules()->where('status', '!=', 'paid')->orderBy('day_number', 'asc')->get();

            foreach ($unpaidSchedules as $schedule) {
                if ($remainingToDistribute <= 0 && !$isFullyPaid) break;

                $neededForThisDay = $schedule->expected_amount - $schedule->paid_amount;
                if ($remainingToDistribute >= $neededForThisDay || $isFullyPaid) {
                    $actualApplied = min($remainingToDistribute, $neededForThisDay);
                    $schedule->update([
                        'paid_amount' => $schedule->expected_amount,
                        'status' => 'paid',
                        'paid_at' => Carbon::now(),
                    ]);
                    $remainingToDistribute = max(0, $remainingToDistribute - $actualApplied);
                } else {
                    $schedule->update([
                        'paid_amount' => $schedule->paid_amount + $remainingToDistribute,
                        'status' => 'partial',
                        'paid_at' => Carbon::now(),
                    ]);
                    $remainingToDistribute = 0;
                }
            }

            if ($isFullyPaid) {
                $loan->schedules()->where('status', '!=', 'paid')->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);
            }

            $loan->update([
                'remaining_balance' => $newRemainingBalance,
                'total_paid' => $newTotalPaid,
                'status' => $isFullyPaid ? 'fully_paid' : 'active',
            ]);

            // 3. Create LoanPayment record (Channel: wallet_auto_deduct, Status: paid)
            $payment = LoanPayment::create([
                'loan_id' => $loan->id,
                'client_id' => $client->id,
                'collector_id' => $loan->collector_id,
                'amount_paid' => $dailyDue,
                'loan_premium_amount' => $loanPart,
                'insurance_premium_amount' => $insurancePart,
                'client_pin_verified' => true,
                'payment_date' => $todayDateStr,
                'status' => 'paid',
                'payment_channel' => 'wallet_auto_deduct',
                'admin_pin_verified_by' => null,
                'admin_pin_verified_at' => Carbon::now(),
                'remitted_at' => Carbon::now(),
                'client_remaining_balance_after' => $newRemainingBalance,
                'notes' => "Automated 12:00 MN wallet deduction.",
            ]);

            // 4. Update Client status & reset missed days
            $client->update([
                'last_payment_date' => $todayDateStr,
                'consecutive_missed_days' => 0,
                'status' => $isFullyPaid ? 'completed' : 'active',
            ]);

            // 5. Host Vault Ledger
            HostVaultLedger::logEntry(
                'in',
                'loan_repayment',
                $dailyDue,
                "Automated 12:00 MN Wallet Loan Repayment for {$client->user->name} (Loan #{$loan->id})",
                'LoanPayment',
                $payment->id,
                null
            );

            // 6. Collector Commission Rule if fully paid
            if ($isFullyPaid && !$loan->collector_commission_paid && $loan->collector) {
                $bonusComm = (float)SystemSetting::get('collector_loan_commission_fixed', 300.00);
                if ($bonusComm > 0) {
                    $loan->collector->increment('commission_balance', $bonusComm);
                    $loan->collector->increment('total_earned_commission', $bonusComm);
                }
                $loan->update(['collector_commission_paid' => true]);

                SystemNotification::sendNotification(
                    $loan->collector->user_id,
                    'collector',
                    "₱" . number_format($bonusComm, 2) . " Fully-Paid Loan Commission Earned!",
                    "Client {$client->user->name} has completed their loan repayment via automated wallet payment.",
                    'payment_received'
                );
            }

            // 7. System Notification to Client
            SystemNotification::sendNotification(
                $client->user_id,
                'client',
                "💰 Auto Loan Payment Deducted (-₱" . number_format($dailyDue, 2) . ")",
                "Daily loan payment of ₱" . number_format($dailyDue, 2) . " (Loan: ₱" . number_format($loanPart, 2) . " + Insurance: ₱" . number_format($insurancePart, 2) . ") was automatically deducted from your wallet balance. Status: PAID. Remaining loan balance: ₱" . number_format($newRemainingBalance, 2),
                'payment_received',
                '/client/dashboard'
            );

            $processedCount++;
            $totalDeducted += $dailyDue;
            $this->info("✓ Deducted ₱" . number_format($dailyDue, 2) . " from {$client->user->name}. Status: PAID.");
        }

        $this->info("Completed automated deductions: {$processedCount} clients processed (Total ₱" . number_format($totalDeducted, 2) . "), {$skippedCount} skipped due to insufficient balance.");
        return Command::SUCCESS;
    }
}
