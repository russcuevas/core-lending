<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Loan;
use App\Models\Client;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ClientUpdateRequest;
use App\Models\CashTurnover;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use Carbon\Carbon;

class HostApprovalController extends Controller
{
    public function index()
    {
        // 1. Pending Loans / Client Applications (Unread first, then latest)
        $pendingLoans = Loan::with(['client.user', 'collector.user', 'encoder'])
            ->where('status', 'pending_host_approval')
            ->orderBy('is_read', 'asc')
            ->latest('id')
            ->get();

        // 2. Pending Cash In / Cash Out transactions (Unread first, then latest)
        $pendingWalletRequests = WalletTransaction::with(['user', 'releasingOfficer'])
            ->where('status', 'pending_host_approval')
            ->orderBy('is_read', 'asc')
            ->latest('id')
            ->get();

        // 3. Pending Collector Accounts (Unread first, then latest)
        $pendingCollectors = User::where('role', 'collector')
            ->where('status', 'pending')
            ->orderBy('is_read', 'asc')
            ->latest('id')
            ->get();

        // 4. Pending Client Detail Updates (Unread first, then latest)
        $pendingClientUpdates = ClientUpdateRequest::with(['client.user', 'requester'])
            ->where('status', 'pending_host_approval')
            ->orderBy('is_read', 'asc')
            ->latest('id')
            ->get();

        // 5. Pending Cash Turnovers from Admin Finance (Unread first, then latest)
        $pendingTurnovers = CashTurnover::with('admin')
            ->where('status', 'pending_host_approval')
            ->orderBy('is_read', 'asc')
            ->latest('id')
            ->get();

        $unreadLoansCount = $pendingLoans->where('is_read', false)->count();
        $unreadWalletCount = $pendingWalletRequests->where('is_read', false)->count();
        $unreadCollectorsCount = $pendingCollectors->where('is_read', false)->count();
        $unreadUpdatesCount = $pendingClientUpdates->where('is_read', false)->count();
        $unreadTurnoversCount = $pendingTurnovers->where('is_read', false)->count();
        $totalUnreadCount = $unreadLoansCount + $unreadWalletCount + $unreadCollectorsCount + $unreadUpdatesCount + $unreadTurnoversCount;

        return view('host.approvals.index', compact(
            'pendingLoans',
            'pendingWalletRequests',
            'pendingCollectors',
            'pendingClientUpdates',
            'pendingTurnovers',
            'unreadLoansCount',
            'unreadWalletCount',
            'unreadCollectorsCount',
            'unreadUpdatesCount',
            'unreadTurnoversCount',
            'totalUnreadCount'
        ));
    }

    public function markAsRead(Request $request)
    {
        $type = $request->input('type'); // 'loan', 'wallet', 'collector', 'update', or 'all'
        $id = $request->input('id');

        if ($type === 'loan' && $id) {
            Loan::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'wallet' && $id) {
            WalletTransaction::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'collector' && $id) {
            User::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'update' && $id) {
            ClientUpdateRequest::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'turnover' && $id) {
            CashTurnover::where('id', $id)->update(['is_read' => true]);
        } elseif ($type === 'all') {
            Loan::where('status', 'pending_host_approval')->update(['is_read' => true]);
            WalletTransaction::where('status', 'pending_host_approval')->update(['is_read' => true]);
            User::where('role', 'collector')->where('status', 'pending')->update(['is_read' => true]);
            ClientUpdateRequest::where('status', 'pending_host_approval')->update(['is_read' => true]);
            CashTurnover::where('status', 'pending_host_approval')->update(['is_read' => true]);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Requests marked as read.');
    }

    public function approveCashTurnover(Request $request, CashTurnover $turnover)
    {
        $turnover->update([
            'status' => 'approved',
            'is_read' => true,
            'host_id' => Auth::id(),
            'approved_at' => Carbon::now(),
            'host_notes' => $request->notes ?? "Physical cash received and vault ledger updated.",
        ]);

        // Host Vault Inflow Ledger entry
        HostVaultLedger::logEntry(
            'in',
            'cash_turnover',
            $turnover->total_amount,
            "Physical Cash Turnover received from Finance Officer {$turnover->admin->name} (Ref: {$turnover->turnover_reference})",
            'CashTurnover',
            $turnover->id,
            Auth::id()
        );

        // Notify Finance Officer
        SystemNotification::sendNotification(
            $turnover->admin_id,
            'admin_releasing',
            'Cash Turnover Approved & Received',
            "Host Superadmin officially received and acknowledged your cash turnover of ₱" . number_format($turnover->total_amount, 2) . " (Ref: {$turnover->turnover_reference}).",
            'approval_notice',
            '/admin/releasing/dashboard'
        );

        return redirect()->to(route('host.approvals.index') . '#tab-turnovers')->with('success', "✓ Cash Turnover #{$turnover->turnover_reference} (₱" . number_format($turnover->total_amount, 2) . ") approved and added to Host Vault balance!");
    }

    public function declineCashTurnover(Request $request, CashTurnover $turnover)
    {
        $request->validate(['reason' => 'required|string']);

        $turnover->update([
            'status' => 'declined',
            'is_read' => true,
            'host_id' => Auth::id(),
            'approved_at' => Carbon::now(),
            'host_notes' => $request->reason,
        ]);

        // Release payments back from turnover
        $turnover->payments()->update(['turnover_id' => null]);

        // Notify Finance Officer
        SystemNotification::sendNotification(
            $turnover->admin_id,
            'admin_releasing',
            'Cash Turnover Declined',
            "Your cash turnover of ₱" . number_format($turnover->total_amount, 2) . " (Ref: {$turnover->turnover_reference}) was declined by Host: {$request->reason}",
            'request_alert',
            '/admin/releasing/dashboard'
        );

        return redirect()->to(route('host.approvals.index') . '#tab-turnovers')->with('success', "Cash Turnover #{$turnover->turnover_reference} was declined.");
    }

    public function approveLoan(Request $request, Loan $loan)
    {
        $loan->update([
            'status' => 'approved_for_release',
            'is_read' => true,
            'host_approved_by' => Auth::id(),
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

        return redirect()->to(route('host.approvals.index') . '#tab-loans')->with('success', "Loan #{$loan->id} approved for release!");
    }

    public function declineLoan(Request $request, Loan $loan)
    {
        $request->validate(['reason' => 'required|string']);

        $loan->update([
            'status' => 'rejected',
            'is_read' => true,
            'decline_reason' => $request->reason,
            'host_approved_by' => Auth::id(),
            'host_approved_at' => Carbon::now(),
        ]);

        if ($loan->client) {
            $loan->client->update(['status' => 'rejected']);
        }

        return redirect()->to(route('host.approvals.index') . '#tab-loans')->with('success', "Loan #{$loan->id} has been declined.");
    }

    public function approveWalletTransaction(Request $request, WalletTransaction $transaction)
    {
        $transaction->update([
            'status' => 'approved_by_host',
            'is_read' => true,
            'host_approved_by' => Auth::id(),
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

        return redirect()->to(route('host.approvals.index') . '#tab-wallet')->with('success', "Transaction #{$transaction->id} approved by Host!");
    }

    public function declineWalletTransaction(Request $request, WalletTransaction $transaction)
    {
        $request->validate(['reason' => 'required|string']);

        $transaction->update([
            'status' => 'declined',
            'is_read' => true,
            'decline_reason' => $request->reason,
            'host_approved_by' => Auth::id(),
            'host_approved_at' => Carbon::now(),
        ]);

        // If it was a cash out, restore or ensure funds remain untouched
        return redirect()->to(route('host.approvals.index') . '#tab-wallet')->with('success', "Transaction #{$transaction->id} has been declined.");
    }

    public function approveCollector(User $user)
    {
        $user->update([
            'status' => 'active',
            'is_read' => true,
        ]);

        SystemNotification::sendNotification(
            $user->id,
            'collector',
            'Account Approved',
            'Your Collector account has been approved by Superadmin. You may now log in.',
            'approval_notice'
        );

        return redirect()->to(route('host.approvals.index') . '#tab-collectors')->with('success', "Collector {$user->name} has been approved and activated!");
    }

    public function declineCollector(User $user)
    {
        $user->update([
            'status' => 'rejected',
            'is_read' => true,
        ]);

        return redirect()->to(route('host.approvals.index') . '#tab-collectors')->with('success', "Collector {$user->name} registration was rejected.");
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

        $clientUpdates = [];
        if (isset($newData['collector_id'])) {
            $clientUpdates['collector_id'] = $newData['collector_id'];
        }
        if (array_key_exists('beneficiary_name', $newData)) {
            $clientUpdates['beneficiary_name'] = $newData['beneficiary_name'];
        }
        if (array_key_exists('beneficiary_phone', $newData)) {
            $clientUpdates['beneficiary_phone'] = $newData['beneficiary_phone'];
        }
        if (!empty($clientUpdates)) {
            $client->update($clientUpdates);
        }

        $updateRequest->update([
            'status' => 'approved',
            'is_read' => true,
            'host_approved_by' => Auth::id(),
            'host_approved_at' => Carbon::now(),
        ]);

        return redirect()->to(route('host.approvals.index') . '#tab-updates')->with('success', 'Client details updated and approved successfully!');
    }

    public function declineClientUpdate(ClientUpdateRequest $updateRequest)
    {
        $updateRequest->update([
            'status' => 'declined',
            'is_read' => true,
            'host_approved_by' => Auth::id(),
            'host_approved_at' => Carbon::now(),
        ]);

        return redirect()->to(route('host.approvals.index') . '#tab-updates')->with('success', 'Client update request was declined.');
    }
}
