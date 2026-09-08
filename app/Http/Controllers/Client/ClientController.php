<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\Collector;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use App\Models\SystemSetting;
use Carbon\Carbon;

class ClientController extends Controller
{
    public function dashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client ?? Client::firstOrCreate([
            'user_id' => $user->id,
            'qr_code_token' => 'CLIENT-QR-' . strtoupper(substr(md5($user->id . $user->phone_number), 0, 10)),
        ]);

        $activeLoan = $client->currentLoan;
        if ($activeLoan) {
            $activeLoan->syncMissedDaysAndExtensions();
            $activeLoan->refresh();
        }

        $loanSchedules = $activeLoan ? $activeLoan->schedules : collect();
        $paidDaysCount = $activeLoan ? $activeLoan->schedules()->where('status', 'paid')->count() : 0;
        $paymentHistory = $activeLoan ? $activeLoan->payments : collect();

        $savingsAccounts = SavingsAccount::where('client_id', $client->id)->latest()->get();
        $walletTransactions = WalletTransaction::where('user_id', $user->id)->latest()->take(10)->get();

        $savingsInterestRate = (float)SystemSetting::get('savings_interest_rate_percent', 10.00);
        $savingsLockInDays = (int)SystemSetting::get('savings_lock_in_days', 60);

        $missedPastDuesCount = $activeLoan ? $activeLoan->schedules()
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->where('status', '!=', 'paid')
            ->count() : 0;

        return view('client.dashboard', compact(
            'client',
            'activeLoan',
            'loanSchedules',
            'paidDaysCount',
            'paymentHistory',
            'savingsAccounts',
            'walletTransactions',
            'missedPastDuesCount',
            'savingsInterestRate',
            'savingsLockInDays'
        ));
    }

    public function requestCashIn(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'notes' => 'nullable|string',
        ]);

        $amount = (float)$request->amount;

        WalletTransaction::create([
            'user_id' => Auth::id(),
            'type' => 'cash_in',
            'amount' => $amount,
            'status' => 'pending_releasing_review',
            'releasing_notes' => $request->notes ?? "Client Cash-In request.",
        ]);

        SystemNotification::sendNotification(
            null,
            'admin_releasing',
            'New Cash In Request (₱' . number_format($amount, 2) . ')',
            "Client " . Auth::user()->name . " requested Cash In of ₱" . number_format($amount, 2) . ".",
            'request_alert',
            '/admin/releasing/dashboard'
        );

        return back()->with('success', 'Cash In request submitted! An officer will process and visit for collection.');
    }

    public function requestCashOut(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'notes' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client;
        $amount = (float)$request->amount;

        if ($client->wallet_balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance for this Cash Out request.');
        }

        WalletTransaction::create([
            'user_id' => Auth::id(),
            'type' => 'cash_out',
            'amount' => $amount,
            'status' => 'pending_releasing_review',
            'releasing_notes' => $request->notes ?? "Client Cash-Out request.",
        ]);

        SystemNotification::sendNotification(
            null,
            'admin_releasing',
            'New Cash Out Request (₱' . number_format($amount, 2) . ')',
            "Client " . $user->name . " requested Cash Out of ₱" . number_format($amount, 2) . ".",
            'request_alert',
            '/admin/releasing/dashboard'
        );

        return back()->with('success', 'Cash Out request submitted! Releasing officer will review and submit to Host for approval.');
    }

    public function createSavings(Request $request)
    {
        $request->validate([
            'deposit_amount' => 'required|numeric|min:500',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client;
        $deposit = (float)$request->deposit_amount;

        // Deduct from wallet balance or check sufficient funds
        if ($client->wallet_balance < $deposit) {
            return back()->with('error', 'Insufficient wallet balance to open a savings deposit. Please Cash In first.');
        }

        $client->decrement('wallet_balance', $deposit);

        $interestRate = (float)SystemSetting::get('savings_interest_rate_percent', 10.00);
        $lockInDays = (int)SystemSetting::get('savings_lock_in_days', 60);
        $commissionRate = (float)SystemSetting::get('collector_savings_commission_percent', 5.00);

        $totalExpectedInterest = round($deposit * ($interestRate / 100), 2);
        $dailyInterestAmount = round($totalExpectedInterest / $lockInDays, 4);
        $collectorCommission = round($deposit * ($commissionRate / 100), 2);

        $savings = SavingsAccount::create([
            'client_id' => $client->id,
            'collector_id' => $client->collector_id,
            'deposit_amount' => $deposit,
            'interest_rate_percent' => $interestRate,
            'lock_in_days' => $lockInDays,
            'daily_interest_amount' => $dailyInterestAmount,
            'total_expected_interest' => $totalExpectedInterest,
            'accumulated_interest_paid' => 0.00,
            'days_credited' => 0,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'maturity_date' => Carbon::today()->addDays($lockInDays)->format('Y-m-d'),
            'status' => 'active',
            'collector_commission_amount' => $collectorCommission,
            'collector_commission_credited' => true,
        ]);

        // Automatically credit dynamic commission to assigned collector
        if ($client->collector && $collectorCommission > 0) {
            $client->collector->increment('commission_balance', $collectorCommission);
            $client->collector->increment('total_earned_commission', $collectorCommission);

            SystemNotification::sendNotification(
                $client->collector->user_id,
                'collector',
                "{$commissionRate}% Savings Commission Earned!",
                "Client {$client->user->name} deposited ₱" . number_format($deposit, 2) . " to Savings. ₱" . number_format($collectorCommission, 2) . " ({$commissionRate}%) added to your balance.",
                'payment_received'
            );
        }

        // Host Vault Inflow Ledger
        HostVaultLedger::logEntry(
            'in',
            'savings_deposit',
            $deposit,
            "Client {$client->user->name} deposited ₱" . number_format($deposit, 2) . " to {$lockInDays}-day {$interestRate}% savings fund.",
            'SavingsAccount',
            $savings->id,
            Auth::id()
        );

        return back()->with('success', "Savings Plan activated! ₱" . number_format($deposit, 2) . " locked for {$lockInDays} days ({$interestRate}% return). Daily interest of ₱" . number_format($dailyInterestAmount, 2) . " will be credited directly to your wallet.");
    }

    public function changePin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required|digits:4',
            'new_pin' => 'required|digits:4|different:current_pin',
            'new_pin_confirmation' => 'required|same:new_pin',
        ], [
            'current_pin.required' => 'Please enter your current 4-digit PIN.',
            'current_pin.digits' => 'Current PIN must be exactly 4 digits.',
            'new_pin.required' => 'Please enter your new 4-digit PIN.',
            'new_pin.digits' => 'New PIN must be exactly 4 digits.',
            'new_pin.different' => 'New PIN must be different from your current PIN.',
            'new_pin_confirmation.same' => 'PIN confirmation does not match the new PIN.',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $isPinValid = ($user->pin_code === $request->current_pin) || Hash::check($request->current_pin, $user->password);

        if (!$isPinValid) {
            return back()->with('error', 'Incorrect current PIN code entered.');
        }

        $user->update([
            'pin_code' => $request->new_pin,
            'password' => Hash::make($request->new_pin),
        ]);

        return back()->with('success', 'Your 4-digit security PIN has been updated successfully!');
    }
}
