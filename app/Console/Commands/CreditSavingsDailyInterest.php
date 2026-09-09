<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use Carbon\Carbon;

class CreditSavingsDailyInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'savings:credit-daily-interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Credit daily savings interest to client wallets and process maturity payout when term ends.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily savings interest distribution...');

        $activeSavings = SavingsAccount::where('status', 'active')
            ->with(['client.user'])
            ->get();

        if ($activeSavings->isEmpty()) {
            $this->info('No active savings accounts found.');
            return 0;
        }

        $creditedCount = 0;
        $maturedCount = 0;

        foreach ($activeSavings as $savings) {
            $client = $savings->client;
            if (!$client || !$client->user) {
                continue;
            }

            $currentDays = (int)$savings->days_credited;
            $lockInDays = (int)$savings->lock_in_days;

            if ($currentDays >= $lockInDays) {
                // Already reached all days, process maturity payout
                $this->processMaturity($savings, $client);
                $maturedCount++;
                continue;
            }

            $nextDay = $currentDays + 1;

            if ($nextDay < $lockInDays) {
                // Days 1 to 59: Credit 1-day interest to wallet
                $dailyInterest = (float)$savings->daily_interest_amount;

                $client->increment('wallet_balance', $dailyInterest);
                $client->refresh();

                $newAccumulated = round((float)$savings->accumulated_interest_paid + $dailyInterest, 2);

                $savings->update([
                    'days_credited' => $nextDay,
                    'accumulated_interest_paid' => $newAccumulated,
                ]);

                WalletTransaction::create([
                    'user_id' => $client->user_id,
                    'type' => 'daily_interest',
                    'amount' => $dailyInterest,
                    'status' => 'completed',
                    'releasing_notes' => "Daily Savings Interest (Day {$nextDay} of {$lockInDays}) for Fund #{$savings->id}.",
                    'user_balance_after' => $client->wallet_balance,
                ]);

                HostVaultLedger::logEntry(
                    'out',
                    'savings_daily_interest',
                    $dailyInterest,
                    "Daily Interest Payout to {$client->user->name} for Savings #{$savings->id} (Day {$nextDay}/{$lockInDays}).",
                    'SavingsAccount',
                    $savings->id
                );

                SystemNotification::sendNotification(
                    $client->user_id,
                    'client',
                    "💰 Daily Interest Credited (+₱" . number_format($dailyInterest, 2) . ")",
                    "Day {$nextDay} of {$lockInDays}: ₱" . number_format($dailyInterest, 2) . " daily savings interest has been credited directly to your wallet.",
                    'payment_received'
                );

                $creditedCount++;
            } else {
                // NextDay is Day 60 (Maturity Day!): Release Capital + Final 1-Day Interest
                $this->processMaturity($savings, $client);
                $maturedCount++;
            }
        }

        $this->info("Completed: {$creditedCount} daily interest payouts credited, {$maturedCount} savings accounts matured.");
        return 0;
    }

    /**
     * Process Maturity payout: Capital + Remaining (1-Day) Interest
     */
    protected function processMaturity(SavingsAccount $savings, $client)
    {
        $deposit = (float)$savings->deposit_amount;
        $totalExpected = (float)$savings->total_expected_interest;
        $alreadyPaid = (float)$savings->accumulated_interest_paid;

        // Calculate remaining interest for the final day (exact difference to prevent any rounding discrepancy)
        $remainingInterest = round(max(0, $totalExpected - $alreadyPaid), 2);
        $totalPayout = $deposit + $remainingInterest;

        $client->increment('wallet_balance', $totalPayout);
        $client->refresh();

        $savings->update([
            'status' => 'matured',
            'days_credited' => $savings->lock_in_days,
            'accumulated_interest_paid' => $totalExpected,
            'maturity_date' => Carbon::today()->format('Y-m-d'),
        ]);

        WalletTransaction::create([
            'user_id' => $client->user_id,
            'type' => 'savings_payout',
            'amount' => $totalPayout,
            'status' => 'completed',
            'releasing_notes' => "Savings Fund #{$savings->id} Matured: ₱" . number_format($deposit, 2) . " Capital Deposit + ₱" . number_format($remainingInterest, 2) . " (Day {$savings->lock_in_days} Final Interest) credited to wallet.",
            'user_balance_after' => $client->wallet_balance,
        ]);

        HostVaultLedger::logEntry(
            'out',
            'savings_payout',
            $totalPayout,
            "Matured Savings Payout to {$client->user->name}: ₱" . number_format($deposit, 2) . " Capital + ₱" . number_format($remainingInterest, 2) . " final interest.",
            'SavingsAccount',
            $savings->id
        );

        SystemNotification::sendNotification(
            $client->user_id,
            'client',
            '🎉 Savings Fund Matured & Capital Released!',
            "Your {$savings->lock_in_days}-day Savings Fund has matured! Total payout of ₱" . number_format($totalPayout, 2) . " (₱" . number_format($deposit, 2) . " Capital + ₱" . number_format($remainingInterest, 2) . " final interest) has been credited to your wallet balance.",
            'payment_received'
        );
    }
}
