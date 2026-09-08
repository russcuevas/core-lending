<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;

class HostTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = HostVaultLedger::with('creator')->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $ledgers = $query->paginate(25);

        $latestLedger = HostVaultLedger::latest('id')->first();
        $vaultBalance = $latestLedger ? (float)$latestLedger->vault_balance_after : 1000000.00;

        return view('host.transactions.index', compact('ledgers', 'vaultBalance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:1',
            'category' => 'required|string|max:50',
            'description' => 'required|string|max:255',
        ]);

        $amount = (float)$request->amount;
        $type = $request->type;
        $category = $request->category;
        $description = $request->description;

        HostVaultLedger::logEntry(
            $type,
            $category,
            $amount,
            $description,
            'ManualHostEntry',
            null,
            Auth::id()
        );

        SystemNotification::sendNotification(
            null,
            'host',
            'Vault Entry Created',
            "Host Superadmin recorded " . ($type === 'in' ? 'deposit/inflow' : 'disbursement/outflow') . " of ₱" . number_format($amount, 2) . " ($category): $description",
            'vault_update'
        );

        return back()->with('success', "Vault transaction of ₱" . number_format($amount, 2) . " successfully recorded!");
    }

    public function adjustBalance(Request $request)
    {
        $request->validate([
            'target_balance' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $targetBalance = (float)$request->target_balance;
        $reason = $request->reason;

        $latestLedger = HostVaultLedger::latest('id')->first();
        $currentBalance = $latestLedger ? (float)$latestLedger->vault_balance_after : 1000000.00;

        $diff = round($targetBalance - $currentBalance, 2);

        if ($diff == 0) {
            return back()->with('info', 'Current vault balance is already equal to the target balance. No adjustments made.');
        }

        $type = $diff > 0 ? 'in' : 'out';
        $amount = abs($diff);

        HostVaultLedger::create([
            'type' => $type,
            'category' => 'vault_adjustment',
            'amount' => $amount,
            'reference_type' => 'ManualAdjustment',
            'reference_id' => null,
            'description' => "Vault balance adjusted from ₱" . number_format($currentBalance, 2) . " to ₱" . number_format($targetBalance, 2) . " (" . ($diff > 0 ? '+' : '-') . "₱" . number_format($amount, 2) . ") - Reason: $reason",
            'vault_balance_after' => $targetBalance,
            'created_by' => Auth::id(),
        ]);

        SystemNotification::sendNotification(
            null,
            'host',
            'Vault Balance Adjusted',
            "Host Superadmin adjusted vault balance from ₱" . number_format($currentBalance, 2) . " to ₱" . number_format($targetBalance, 2) . " (Reason: $reason)",
            'vault_update'
        );

        return back()->with('success', "Vault balance successfully adjusted to ₱" . number_format($targetBalance, 2) . "!");
    }

    public function update(Request $request, HostVaultLedger $ledger)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string|max:50',
        ]);

        $ledger->update([
            'description' => $request->description,
            'category' => $request->category,
        ]);

        return back()->with('success', "Ledger record #{$ledger->id} updated successfully!");
    }
}
