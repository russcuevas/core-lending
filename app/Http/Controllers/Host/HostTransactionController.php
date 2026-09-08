<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostVaultLedger;
use App\Models\LoanPayment;
use App\Models\WalletTransaction;

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

        return view('host.transactions.index', compact('ledgers'));
    }
}
