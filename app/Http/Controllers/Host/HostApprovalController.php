<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\Client;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ClientUpdateRequest;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use Carbon\Carbon;

class HostApprovalController extends Controller
{
    public function index()
    {
        // 1. Pending Loans / Client Applications
        $pendingLoans = Loan::with(['client.user', 'collector.user', 'encoder'])
            ->where('status', 'pending_host_approval')
            ->latest()
            ->get();

        // 2. Pending Cash In / Cash Out transactions
        $pendingWalletRequests = WalletTransaction::with(['user', 'releasingOfficer'])
            ->where('status', 'pending_host_approval')
            ->latest()
            ->get();

        // 3. Pending Collector Accounts
        $pendingCollectors = User::where('role', 'collector')
            ->where('status', 'pending')
            ->latest()
            ->get();

        // 4. Pending Client Detail Updates
        $pendingClientUpdates = ClientUpdateRequest::with(['client.user', 'requester'])
            ->where('status', 'pending_host_approval')
            ->latest()
            ->get();

        return view('host.approvals.index', compact(
            'pendingLoans',
            'pendingWalletRequests',
            'pendingCollectors',
            'pendingClientUpdates'
        ));
    }

    public function approveLoan(Request $request, Loan $loan)
    {
        $loan->update([
            'status' => 'approved_for_release',
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
            'release_note' => $request->notes ?? $loan->release_note,
        ]);

        // Tag client as active / approved
        if ($loan->client) {
            $loan->client->update([
                'status' => 'active',
                'current_loan_id' => $loan->id,
            ]);
            $loan->client->user->update(['status' => 'active']);
        }

        // Notify Releasing Officer
        SystemNotification::sendNotification(
            null,
            'admin_releasing',
            'Loan Approved by Host for Release',
            "Loan #{$loan->id} for {$loan->client->user->name} (₱" . number_format($loan->principal_amount, 2) . ") is approved for release.",
            'approval_notice',
            '/admin/releasing/dashboard'
        );

        return back()->with('success', "Loan #{$loan->id} approved for release!");
    }

    public function declineLoan(Request $request, Loan $loan)
    {
        $request->validate(['reason' => 'required|string']);

        $loan->update([
            'status' => 'rejected',
            'decline_reason' => $request->reason,
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
        ]);

        if ($loan->client) {
            $loan->client->update(['status' => 'rejected']);
        }

        return back()->with('success', "Loan #{$loan->id} has been declined.");
    }

    public function approveWalletTransaction(Request $request, WalletTransaction $transaction)
    {
        $transaction->update([
            'status' => 'approved_by_host',
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
            'host_notes' => $request->notes,
        ]);

        // Notify Releasing Officer to execute physical release / collection
        SystemNotification::sendNotification(
            null,
            'admin_releasing',
            'Transaction Approved by Host',
            "Host approved " . strtoupper(str_replace('_', ' ', $transaction->type)) . " of ₱" . number_format($transaction->amount, 2) . " for {$transaction->user->name}.",
            'approval_notice',
            '/admin/releasing/dashboard'
        );

        return back()->with('success', "Transaction #{$transaction->id} approved by Host!");
    }

    public function declineWalletTransaction(Request $request, WalletTransaction $transaction)
    {
        $request->validate(['reason' => 'required|string']);

        $transaction->update([
            'status' => 'declined',
            'decline_reason' => $request->reason,
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
        ]);

        // If it was a cash out, restore or ensure funds remain untouched
        return back()->with('success', "Transaction #{$transaction->id} has been declined.");
    }

    public function approveCollector(User $user)
    {
        $user->update(['status' => 'active']);

        SystemNotification::sendNotification(
            $user->id,
            'collector',
            'Account Approved',
            'Your Collector account has been approved by Superadmin. You may now log in.',
            'approval_notice'
        );

        return back()->with('success', "Collector {$user->name} has been approved and activated!");
    }

    public function declineCollector(User $user)
    {
        $user->update(['status' => 'rejected']);

        return back()->with('success', "Collector {$user->name} registration was rejected.");
    }

    public function approveClientUpdate(ClientUpdateRequest $updateRequest)
    {
        $client = $updateRequest->client;
        $newData = $updateRequest->new_data;

        // Apply new data to user
        $client->user->update([
            'name' => $newData['name'] ?? $client->user->name,
            'phone_number' => $newData['phone_number'] ?? $client->user->phone_number,
            'address' => $newData['address'] ?? $client->user->address,
        ]);

        if (isset($newData['collector_id'])) {
            $client->update(['collector_id' => $newData['collector_id']]);
        }

        $updateRequest->update([
            'status' => 'approved',
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Client details updated and approved successfully!');
    }

    public function declineClientUpdate(ClientUpdateRequest $updateRequest)
    {
        $updateRequest->update([
            'status' => 'declined',
            'host_approved_by' => auth()->id(),
            'host_approved_at' => Carbon::now(),
        ]);

        return back()->with('success', 'Client update request was declined.');
    }
}
