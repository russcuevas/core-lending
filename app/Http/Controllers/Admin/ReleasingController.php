<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\WalletTransaction;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\Collector;
use App\Models\User;
use App\Models\Client;
use App\Models\CashTurnover;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use App\Models\SystemSetting;
use Carbon\Carbon;

class ReleasingController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        // 1. Pending incoming requests waiting for Finance Officer review
        $pendingReviews = WalletTransaction::with('user')
            ->where('status', 'pending_releasing_review')
            ->latest()
            ->get();

        // 2. Approved by Host, ready for Finance Officer to execute (PIN & Camera Photo Proof)
        $approvedReadyToRelease = WalletTransaction::with('user')
            ->where('status', 'approved_by_host')
            ->latest()
            ->get();

        // 3. Loans approved by Host waiting for physical disbursement
        $loansReadyToDisburse = Loan::with(['client.user', 'collector.user'])
            ->where('status', 'approved_for_release')
            ->latest()
            ->get();

        // 4. Pending Collector Remittances (Collections awaiting Admin Finance PIN verification)
        $pendingRemittances = LoanPayment::where('status', 'processing')
            ->with(['collector.user', 'client.user', 'loan'])
            ->latest()
            ->get();

        $collectorsWithPendingRemittances = $pendingRemittances->groupBy('collector_id');

        $totalPendingRemittanceAmount = $pendingRemittances->sum('amount_paid');
        $totalRemittedToday = LoanPayment::where('status', 'paid')
            ->whereDate('remitted_at', $today)
            ->sum('amount_paid');

        $totalRemittedAllTime = LoanPayment::where('status', 'paid')->sum('amount_paid');

        // Cash on Hand: All Remitted Collections minus All Cash Turned Over to Host (excluding declined turnovers)
        $totalTurnedOver = CashTurnover::where('status', '!=', 'declined')->sum('total_amount');
        $cashOnHand = max(0, $totalRemittedAllTime - $totalTurnedOver);

        // 5. Recent Cash Turnovers to Host
        $recentTurnovers = CashTurnover::with(['admin', 'host'])->latest()->take(10)->get();

        // 6. Completed disbursements today
        $completedToday = WalletTransaction::whereIn('status', ['completed'])
            ->whereDate('updated_at', $today)
            ->count();

        return view('admin.releasing.dashboard', compact(
            'pendingReviews',
            'approvedReadyToRelease',
            'loansReadyToDisburse',
            'pendingRemittances',
            'collectorsWithPendingRemittances',
            'totalPendingRemittanceAmount',
            'totalRemittedToday',
            'totalRemittedAllTime',
            'cashOnHand',
            'recentTurnovers',
            'completedToday'
        ));
    }

    public function submitReview(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'action' => 'required|in:approve,decline',
            'notes' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
            'decline_reason' => 'required_if:action,decline|nullable|string',
        ]);

        if ($request->action === 'approve') {
            $transaction->update([
                'status' => 'pending_host_approval',
                'releasing_officer_id' => Auth::id(),
                'releasing_notes' => $request->notes,
                'releasing_scheduled_date' => $request->scheduled_date ?? Carbon::today()->format('Y-m-d'),
            ]);

            SystemNotification::sendNotification(
                null,
                'host',
                'Transaction Recommended for Approval',
                "Releasing Officer recommended {$transaction->type} of ₱" . number_format($transaction->amount, 2) . " for {$transaction->user->name}. Please review and approve.",
                'approval_needed',
                '/host/approvals'
            );

            return back()->with('success', 'Request reviewed and forwarded to Host Superadmin for final approval.');
        } else {
            $transaction->update([
                'status' => 'declined',
                'releasing_officer_id' => Auth::id(),
                'decline_reason' => $request->decline_reason,
            ]);

            SystemNotification::sendNotification(
                $transaction->user_id,
                null,
                'Transaction Request Declined',
                "Your request for {$transaction->type} of ₱" . number_format($transaction->amount, 2) . " was declined: {$request->decline_reason}",
                'request_alert'
            );

            return back()->with('success', 'Request declined and notified to user.');
        }
    }

    public function executeDisbursement(Request $request, WalletTransaction $transaction)
    {
        $request->validate([
            'client_pin' => 'required|digits:4',
            'photo_proof' => 'required|string', // base64 data URL from camera
        ]);

        $user = $transaction->user;

        // Verify Client PIN
        $isPinValid = ($user->pin_code === $request->client_pin) || Hash::check($request->client_pin, $user->password);
        if (!$isPinValid) {
            return back()->with('error', 'Invalid Client PIN code entered. Verification failed.');
        }

        // Save base64 photo proof to public/uploads/transaction_proofs
        $photoData = $request->photo_proof;
        $proofFilename = 'proof_tx_' . $transaction->id . '_' . time() . '.jpg';
        $proofPath = 'uploads/transaction_proofs/' . $proofFilename;
        
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $data = substr($photoData, strpos($photoData, ',') + 1);
            $data = base64_decode($data);
            file_put_contents(public_path($proofPath), $data);
        }

        $amount = (float)$transaction->amount;

        if ($transaction->type === 'cash_in') {
            // Cash In: Client receives wallet credit, Host receives physical cash
            if ($user->client) {
                $user->client->increment('wallet_balance', $amount);
                $newBalance = $user->client->wallet_balance;
            } else {
                $newBalance = $amount;
            }

            // Host vault ledger entry (Cash In)
            HostVaultLedger::logEntry(
                'in',
                'cash_in',
                $amount,
                "Cash-In processed for {$user->name}",
                'WalletTransaction',
                $transaction->id,
                Auth::id()
            );

        } elseif ($transaction->type === 'cash_out') {
            // Cash Out: Deduct client wallet balance, Host releases physical cash
            if ($user->client) {
                if ($user->client->wallet_balance < $amount) {
                    return back()->with('error', 'Client does not have sufficient wallet balance.');
                }
                $user->client->decrement('wallet_balance', $amount);
                $newBalance = $user->client->wallet_balance;
            } else {
                $newBalance = 0.00;
            }

            // Host vault ledger entry (Cash Out)
            HostVaultLedger::logEntry(
                'out',
                'cash_out',
                $amount,
                "Cash-Out released to {$user->name}",
                'WalletTransaction',
                $transaction->id,
                Auth::id()
            );

        } elseif ($transaction->type === 'collector_cashout') {
            // Collector Cashout: Deduct collector commission balance, Host releases cash
            if ($user->collector) {
                if ($user->collector->commission_balance < $amount) {
                    return back()->with('error', 'Collector does not have sufficient commission balance.');
                }
                $user->collector->decrement('commission_balance', $amount);
                $newBalance = $user->collector->commission_balance;
            } else {
                $newBalance = 0.00;
            }

            // Host vault ledger entry
            HostVaultLedger::logEntry(
                'out',
                'cash_out',
                $amount,
                "Collector Commission Cashout released to {$user->name}",
                'WalletTransaction',
                $transaction->id,
                Auth::id()
            );
        }

        $transaction->update([
            'status' => 'completed',
            'proof_image_path' => $proofPath,
            'pin_verified' => true,
            'user_balance_after' => $newBalance ?? 0.00,
            'updated_at' => Carbon::now(),
        ]);

        SystemNotification::sendNotification(
            $user->id,
            null,
            'Transaction Completed',
            "Your " . strtoupper(str_replace('_', ' ', $transaction->type)) . " of ₱" . number_format($amount, 2) . " has been successfully released and posted.",
            'payment_received'
        );

        return back()->with('success', "Transaction #{$transaction->id} successfully disbursed, verified with PIN & photo proof!");
    }

    public function executeLoanRelease(Request $request, Loan $loan)
    {
        $request->validate([
            'client_pin' => 'required|digits:4',
            'photo_proof' => 'required|string',
        ]);

        $clientUser = $loan->client->user;

        // Verify PIN
        $isPinValid = ($clientUser->pin_code === $request->client_pin) || Hash::check($request->client_pin, $clientUser->password);
        if (!$isPinValid) {
            return back()->with('error', 'Invalid Client PIN code. Cannot release funds.');
        }

        // Save proof photo
        $proofFilename = 'proof_loan_' . $loan->id . '_' . time() . '.jpg';
        $proofPath = 'uploads/transaction_proofs/' . $proofFilename;
        $photoData = $request->photo_proof;
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $data = substr($photoData, strpos($photoData, ',') + 1);
            $data = base64_decode($data);
            file_put_contents(public_path($proofPath), $data);
        }

        $loan->update([
            'status' => 'active',
            'release_date' => Carbon::today()->format('Y-m-d'),
            'releasing_officer_id' => Auth::id(),
            'disbursement_proof_path' => $proofPath,
        ]);

        $loan->client->update([
            'status' => 'active',
            'current_loan_id' => $loan->id,
        ]);

        // Host vault ledger entry (Loan disbursement out)
        HostVaultLedger::logEntry(
            'out',
            'loan_release',
            $loan->principal_amount,
            "Loan Principal Released for {$clientUser->name} (Loan #{$loan->id})",
            'Loan',
            $loan->id,
            Auth::id()
        );

        SystemNotification::sendNotification(
            $clientUser->id,
            null,
            'Loan Funds Released!',
            "Your loan of ₱" . number_format($loan->principal_amount, 2) . " has been released. Your 60-day repayment schedule is now active.",
            'approval_notice',
            '/client/dashboard'
        );

        return back()->with('success', "Loan #{$loan->id} successfully released to {$clientUser->name}!");
    }

    public function receiveRemittance(Request $request)
    {
        $request->validate([
            'admin_pin' => 'required|digits:4',
            'collector_id' => 'required|exists:collectors,id',
            'payment_ids' => 'nullable|array',
            'payment_ids.*' => 'exists:loan_payments,id',
        ]);

        /** @var \App\Models\User $adminUser */
        $adminUser = Auth::user();

        // Verify Admin Finance PIN
        $isPinValid = ($adminUser->pin_code === $request->admin_pin) || Hash::check($request->admin_pin, $adminUser->password);
        if (!$isPinValid) {
            return back()->with('error', 'Incorrect Admin Finance PIN code. Verification failed.');
        }

        $collector = Collector::with('user')->findOrFail($request->collector_id);

        $query = LoanPayment::where('collector_id', $collector->id)
            ->where('status', 'processing');

        if ($request->filled('payment_ids')) {
            $query->whereIn('id', $request->payment_ids);
        }

        $payments = $query->with(['loan.client.user', 'client.user'])->get();

        if ($payments->isEmpty()) {
            return back()->with('error', 'No pending processing payments found for this collector.');
        }

        $totalRemitted = 0.00;

        foreach ($payments as $payment) {
            $loan = $payment->loan;
            $client = $payment->client;
            $clientUser = $client->user;
            $amountPaid = (float)$payment->amount_paid;
            $totalRemitted += $amountPaid;

            // Apply payment to loan balance & schedules
            $newRemainingBalance = max(0, $loan->remaining_balance - $amountPaid);
            $newTotalPaid = $loan->total_paid + $amountPaid;
            $isFullyPaid = ($newRemainingBalance <= 0);

            $remainingToDistribute = $amountPaid;
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

            // Update payment to 'paid' & stamped with Admin Finance PIN verification
            $payment->update([
                'status' => 'paid',
                'remitted_at' => Carbon::now(),
                'admin_pin_verified_by' => $adminUser->id,
                'admin_pin_verified_at' => Carbon::now(),
                'client_remaining_balance_after' => $newRemainingBalance,
            ]);

            // Update client last payment date
            $client->update([
                'last_payment_date' => Carbon::today()->format('Y-m-d'),
                'consecutive_missed_days' => 0,
                'status' => $isFullyPaid ? 'completed' : 'active',
            ]);

            // Host Vault Inflow Ledger
            HostVaultLedger::logEntry(
                'in',
                'loan_repayment',
                $amountPaid,
                "Daily loan repayment verified & remitted for {$clientUser->name} (Collector: {$collector->user->name}, Verified by Finance Officer: {$adminUser->name})",
                'LoanPayment',
                $payment->id,
                $adminUser->id
            );

            // Commission rule for collector if fully paid
            if ($isFullyPaid && !$loan->collector_commission_paid) {
                $bonusComm = (float)SystemSetting::get('collector_loan_commission_fixed', 300.00);
                if ($bonusComm > 0) {
                    $collector->increment('commission_balance', $bonusComm);
                    $collector->increment('total_earned_commission', $bonusComm);
                }
                $loan->update(['collector_commission_paid' => true]);

                SystemNotification::sendNotification(
                    $collector->user_id,
                    'collector',
                    "₱" . number_format($bonusComm, 2) . " Fully-Paid Loan Commission Earned!",
                    "Congratulations! Client {$clientUser->name} has fully paid their loan. ₱" . number_format($bonusComm, 2) . " commission credited to your balance.",
                    'payment_received'
                );
            }

            // Client notification: Paid status confirmed
            SystemNotification::sendNotification(
                $clientUser->id,
                'client',
                "Payment Confirmed & Verified (PAID)",
                "Your daily payment of ₱" . number_format($amountPaid, 2) . " has been received at the office and officially verified as PAID. Remaining Balance: ₱" . number_format($newRemainingBalance, 2),
                'payment_received',
                '/client/dashboard'
            );
        }

        // Notify Collector that remittance was accepted
        SystemNotification::sendNotification(
            $collector->user_id,
            'collector',
            "Remittance Confirmed by Finance (₱" . number_format($totalRemitted, 2) . ")",
            "Finance Officer {$adminUser->name} verified and accepted your collection remittance of ₱" . number_format($totalRemitted, 2) . " (" . $payments->count() . " client payments).",
            'payment_received',
            '/collector/dashboard'
        );

        return back()->with('success', "✓ Remittance of ₱" . number_format($totalRemitted, 2) . " (" . $payments->count() . " collections from {$collector->user->name}) successfully verified and updated to PAID!");
    }

    public function submitCashTurnover(Request $request)
    {
        $totalRemittedAllTime = LoanPayment::where('status', 'paid')->sum('amount_paid');
        $totalTurnedOver = CashTurnover::where('status', '!=', 'declined')->sum('total_amount');
        $availableCashOnHand = max(0, $totalRemittedAllTime - $totalTurnedOver);

        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . max(1, $availableCashOnHand),
            'notes' => 'nullable|string',
        ], [
            'amount.max' => 'Turnover amount cannot exceed available Remitted Cash on Hand (₱' . number_format($availableCashOnHand, 2) . ').',
        ]);

        $amount = (float)$request->amount;
        $turnoverRef = 'TO-' . Carbon::today()->format('Ymd') . '-' . strtoupper(Str::random(6));

        // Get un-turned-over remitted payments to compute breakdown
        $unTurnedOverPayments = LoanPayment::where('status', 'paid')
            ->whereNull('turnover_id')
            ->get();

        $loanPremiumSum = $unTurnedOverPayments->sum('loan_premium_amount');
        $insurancePremiumSum = $unTurnedOverPayments->sum('insurance_premium_amount');

        $turnover = CashTurnover::create([
            'turnover_reference' => $turnoverRef,
            'admin_id' => Auth::id(),
            'total_amount' => $amount,
            'loan_collection_amount' => $loanPremiumSum > 0 ? min($amount, $loanPremiumSum) : $amount,
            'insurance_collection_amount' => $insurancePremiumSum > 0 ? min($amount, $insurancePremiumSum) : 0.00,
            'date' => Carbon::today()->format('Y-m-d'),
            'status' => 'pending_host_approval',
            'admin_notes' => $request->notes ?? ("Physical Cash Turnover from Finance Officer " . Auth::user()->name),
            'is_read' => false,
        ]);

        // Link un-turned-over payments up to the turned over amount
        $accumulated = 0;
        foreach ($unTurnedOverPayments as $payment) {
            if ($accumulated >= $amount) break;
            $payment->update(['turnover_id' => $turnover->id]);
            $accumulated += (float)$payment->amount_paid;
        }

        // Notify Host
        SystemNotification::sendNotification(
            null,
            'host',
            "💵 Cash Turnover Submitted (₱" . number_format($amount, 2) . ")",
            "Finance Officer " . Auth::user()->name . " turned over physical cash of ₱" . number_format($amount, 2) . " (Ref: {$turnoverRef}). Please count, acknowledge, and approve in Host Approvals.",
            'approval_needed',
            '/host/approvals#tab-turnovers'
        );

        return back()->with('success', "✓ Cash Turnover of ₱" . number_format($amount, 2) . " (Ref: {$turnoverRef}) submitted to Host Superadmin for approval!");
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

        return back()->with('success', 'Your Admin Finance 4-digit security PIN has been updated successfully!');
    }
}
