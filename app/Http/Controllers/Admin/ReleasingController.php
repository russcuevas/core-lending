<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\WalletTransaction;
use App\Models\Loan;
use App\Models\User;
use App\Models\Client;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use Carbon\Carbon;

class ReleasingController extends Controller
{
    public function dashboard()
    {
        // 1. Pending incoming requests waiting for Releasing Officer review
        $pendingReviews = WalletTransaction::with('user')
            ->where('status', 'pending_releasing_review')
            ->latest()
            ->get();

        // 2. Approved by Host, ready for Releasing Officer to execute (PIN & Camera Photo Proof)
        $approvedReadyToRelease = WalletTransaction::with('user')
            ->where('status', 'approved_by_host')
            ->latest()
            ->get();

        // 3. Loans approved by Host waiting for physical disbursement
        $loansReadyToDisburse = Loan::with(['client.user', 'collector.user'])
            ->where('status', 'approved_for_release')
            ->latest()
            ->get();

        // 4. Completed disbursements today
        $completedToday = WalletTransaction::whereIn('status', ['completed'])
            ->whereDate('updated_at', Carbon::today())
            ->count();

        return view('admin.releasing.dashboard', compact(
            'pendingReviews',
            'approvedReadyToRelease',
            'loansReadyToDisburse',
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
}
