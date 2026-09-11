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
        $walletTransactions = WalletTransaction::where('user_id', $user->id)->latest()->take(15)->get();

        // Incoming / Approved by Host transactions ready for execution
        $incomingApprovedWalletTx = WalletTransaction::where('user_id', $user->id)
            ->where('status', 'approved_by_host')
            ->latest()
            ->get();

        // Pending review / approval transactions
        $pendingUnderReviewWalletTx = WalletTransaction::where('user_id', $user->id)
            ->whereIn('status', ['pending_releasing_review', 'pending_host_approval'])
            ->latest()
            ->get();

        // Loan ready for release
        $approvedLoanForRelease = Loan::where('client_id', $client->id)
            ->where('status', 'approved_for_release')
            ->latest()
            ->first();

        $savingsInterestRate = (float)SystemSetting::get('savings_interest_rate_percent', 10.00);
        $savingsLockInDays = (int)SystemSetting::get('savings_lock_in_days', 60);

        $missedPastDuesCount = $activeLoan ? $activeLoan->schedules()
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->where('status', '!=', 'paid')
            ->count() : 0;

        $loanInterestRate = (float)SystemSetting::get('loan_interest_rate_percent', 10.00);
        $loanTermDays = (int)SystemSetting::get('loan_term_days', 60);
        $loanInsurancePremium = (float)($activeLoan->insurance_premium_daily ?? SystemSetting::get('loan_insurance_premium_daily', 25.00));

        return view('client.dashboard', compact(
            'client',
            'activeLoan',
            'loanSchedules',
            'paidDaysCount',
            'paymentHistory',
            'savingsAccounts',
            'walletTransactions',
            'incomingApprovedWalletTx',
            'pendingUnderReviewWalletTx',
            'approvedLoanForRelease',
            'missedPastDuesCount',
            'savingsInterestRate',
            'savingsLockInDays',
            'loanInterestRate',
            'loanTermDays',
            'loanInsurancePremium'
        ));
    }

    public function insurancePolicy()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client ?? Client::firstOrCreate([
            'user_id' => $user->id,
            'qr_code_token' => 'CLIENT-QR-' . strtoupper(substr(md5($user->id . $user->phone_number), 0, 10)),
        ]);

        $activeLoan = $client->currentLoan;
        $insurancePremiumDaily = (float)($activeLoan->insurance_premium_daily ?? SystemSetting::get('loan_insurance_premium_daily', 25.00));
        
        $isCoveredToday = false;
        $coveredDaysCount = 0;
        if ($activeLoan && $activeLoan->status === 'active') {
            $coveredDaysCount = $activeLoan->schedules()->where('status', 'paid')->count();
            $todaySchedule = $activeLoan->schedules()->where('due_date', Carbon::today()->toDateString())->first();
            if ($todaySchedule && $todaySchedule->status === 'paid') {
                $isCoveredToday = true;
            } elseif ($activeLoan->remaining_balance <= 0) {
                $isCoveredToday = true;
            }
        }

        return view('client.insurance', compact('client', 'activeLoan', 'insurancePremiumDaily', 'isCoveredToday', 'coveredDaysCount'));
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

    public function requestRenewal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:500',
            'notes' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client;

        if ($client->currentLoan && in_array($client->currentLoan->status, ['active', 'pending_host_approval', 'approved_for_release'])) {
            return back()->with('error', 'You currently have an ongoing or pending loan in progress.');
        }

        $termDays = (int)SystemSetting::get('loan_term_days', 60);
        $interestPercent = (float)SystemSetting::get('loan_interest_rate_percent', 10.00);
        $insuranceDaily = (float)SystemSetting::get('loan_insurance_premium_daily', 5.00);
        $principal = (float)$request->amount;
        $interestTotal = $principal * ($interestPercent / 100);
        $totalPayable = $principal + $interestTotal;
        $loanPremiumDaily = round($totalPayable / $termDays, 2);
        $totalDailyPayable = $loanPremiumDaily + $insuranceDaily;

        $client->update(['status' => 'pending_host_approval']);

        $loan = Loan::create([
            'client_id' => $client->id,
            'collector_id' => $client->collector_id,
            'principal_amount' => $principal,
            'interest_rate_percent' => $interestPercent,
            'total_payable' => $totalPayable,
            'daily_installment' => $loanPremiumDaily,
            'loan_premium_daily' => $loanPremiumDaily,
            'insurance_premium_daily' => $insuranceDaily,
            'total_daily_payable' => $totalDailyPayable,
            'term_days' => $termDays,
            'remaining_balance' => $totalPayable,
            'total_paid' => 0.00,
            'status' => 'pending_host_approval',
            'release_note' => $request->notes ?? "Self-service Client Loan Renewal Request",
        ]);

        $client->update(['current_loan_id' => $loan->id]);

        for ($day = 1; $day <= $termDays; $day++) {
            $expectedForDay = ($day === $termDays)
                ? round($totalPayable - ($loanPremiumDaily * ($termDays - 1)), 2)
                : $loanPremiumDaily;

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'day_number' => $day,
                'due_date' => null,
                'expected_amount' => $expectedForDay,
                'paid_amount' => 0.00,
                'status' => 'unpaid',
            ]);
        }

        SystemNotification::sendNotification(
            null,
            'host',
            'Client Loan Renewal Request',
            "Client {$user->name} requested a Loan Renewal of ₱" . number_format($principal, 2) . " for Host Approval.",
            'approval_needed',
            '/host/approvals'
        );

        return back()->with('success', 'Your loan renewal request for ₱' . number_format($principal, 2) . ' has been submitted for Host Approval!');
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

    public function simulateDailyInterest(SavingsAccount $savings)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client;

        if (!$client || $savings->client_id !== $client->id) {
            return back()->with('error', 'Unauthorized access to this savings account.');
        }

        if ($savings->status !== 'active') {
            return back()->with('error', 'This savings fund has already matured or is no longer active.');
        }

        $currentDays = (int)$savings->days_credited;
        $lockInDays = (int)$savings->lock_in_days;
        $nextDay = $currentDays + 1;

        if ($nextDay < $lockInDays) {
            $dailyInterest = (float)$savings->daily_interest_amount;

            $client->increment('wallet_balance', $dailyInterest);
            $client->refresh();

            $newAccumulated = round((float)$savings->accumulated_interest_paid + $dailyInterest, 2);

            $savings->update([
                'days_credited' => $nextDay,
                'accumulated_interest_paid' => $newAccumulated,
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
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
                $savings->id,
                Auth::id()
            );

            SystemNotification::sendNotification(
                $user->id,
                'client',
                "💰 Daily Interest Credited (+₱" . number_format($dailyInterest, 2) . ")",
                "Day {$nextDay} of {$lockInDays}: ₱" . number_format($dailyInterest, 2) . " daily savings interest has been credited directly to your wallet.",
                'payment_received'
            );

            return back()->with('success', "💰 1-Day Daily Interest Simulated! ₱" . number_format($dailyInterest, 2) . " credited to your wallet (Day {$nextDay} of {$lockInDays}).");
        } else {
            return $this->matureSavings($savings);
        }
    }

    public function matureSavings(SavingsAccount $savings)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $user->client;

        if (!$client || $savings->client_id !== $client->id) {
            return back()->with('error', 'Unauthorized access to this savings account.');
        }

        if ($savings->status !== 'active') {
            return back()->with('error', 'This savings fund has already matured or is no longer active.');
        }

        $deposit = (float)$savings->deposit_amount;
        $totalExpected = (float)$savings->total_expected_interest;
        $alreadyPaid = (float)$savings->accumulated_interest_paid;

        // Exact remaining interest for the final day (or remaining term)
        $remainingInterest = round(max(0, $totalExpected - $alreadyPaid), 2);
        $totalPayout = $deposit + $remainingInterest;

        // 1. Mark savings as matured & update metrics
        $savings->update([
            'status' => 'matured',
            'days_credited' => $savings->lock_in_days,
            'accumulated_interest_paid' => $totalExpected,
            'maturity_date' => Carbon::today()->format('Y-m-d'),
        ]);

        // 2. Credit Capital + Remaining Interest to Client Wallet
        $client->increment('wallet_balance', $totalPayout);
        $client->refresh();

        // 3. Create completed WalletTransaction record
        WalletTransaction::create([
            'user_id' => $user->id,
            'type' => 'savings_payout',
            'amount' => $totalPayout,
            'status' => 'completed',
            'releasing_notes' => "Savings Fund #{$savings->id} {$savings->lock_in_days}-Day Maturity: ₱" . number_format($deposit, 2) . " Capital Deposit + ₱" . number_format($remainingInterest, 2) . " final interest credited to wallet.",
            'user_balance_after' => $client->wallet_balance,
        ]);

        // 4. Log Host Vault Outflow
        HostVaultLedger::logEntry(
            'out',
            'savings_payout',
            $totalPayout,
            "Matured Savings Payout to {$client->user->name}: ₱" . number_format($deposit, 2) . " Capital + ₱" . number_format($remainingInterest, 2) . " final interest.",
            'SavingsAccount',
            $savings->id,
            Auth::id()
        );

        // 5. System Notification
        SystemNotification::sendNotification(
            $user->id,
            'client',
            '🎉 Savings Fund Matured & Capital Released!',
            "Your {$savings->lock_in_days}-day Savings Fund has matured! Total payout of ₱" . number_format($totalPayout, 2) . " (₱" . number_format($deposit, 2) . " Capital + ₱" . number_format($remainingInterest, 2) . " final interest) is now in your available wallet balance.",
            'payment_received'
        );

        return back()->with('success', "🎉 Maturity Complete! ₱" . number_format($totalPayout, 2) . " (₱" . number_format($deposit, 2) . " Capital + ₱" . number_format($remainingInterest, 2) . " Final Interest) has been credited to your available wallet balance!");
    }
}
