<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\Expense;
use App\Models\HostVaultLedger;
use App\Models\ClientUpdateRequest;
use Carbon\Carbon;

class HostDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Vault Balance
        $latestLedger = HostVaultLedger::latest('id')->first();
        $vaultBalance = $latestLedger ? $latestLedger->vault_balance_after : 1000000.00;

        // 2. Today's Statistics
        $todayCashIn = WalletTransaction::where('type', 'cash_in')
            ->where('status', 'completed')
            ->whereDate('updated_at', $today)
            ->sum('amount');

        $todayCashOut = WalletTransaction::whereIn('type', ['cash_out', 'collector_cashout'])
            ->where('status', 'completed')
            ->whereDate('updated_at', $today)
            ->sum('amount');

        $todayLoanCollections = LoanPayment::whereDate('payment_date', $today)->sum('amount_paid');
        $todaySavingsDeposits = SavingsAccount::whereDate('created_at', $today)->sum('deposit_amount');
        $todayExpenses = Expense::whereDate('date', $today)->sum('amount');

        // Total Cumulative Statistics
        $totalActiveLoans = Loan::where('status', 'active')->sum('remaining_balance');
        $totalActiveClientsCount = Client::where('status', 'active')->count();
        $totalCollectorsCount = Collector::count();
        $totalSavingsActive = SavingsAccount::where('status', 'active')->sum('deposit_amount');

        // 3. Pending Approvals count
        $pendingLoanCount = Loan::where('status', 'pending_host_approval')->count();
        $pendingWalletCount = WalletTransaction::where('status', 'pending_host_approval')->count();
        $pendingCollectorCount = User::where('role', 'collector')->where('status', 'pending')->count();
        $pendingClientUpdatesCount = ClientUpdateRequest::where('status', 'pending_host_approval')->count();

        $totalPendingApprovals = $pendingLoanCount + $pendingWalletCount + $pendingCollectorCount + $pendingClientUpdatesCount;

        // 4. Recent transactions
        $recentTransactions = HostVaultLedger::with('creator')->latest()->take(10)->get();

        // 5. Weekly Chart data (last 7 days)
        $chartLabels = [];
        $chartCashIn = [];
        $chartCashOut = [];
        $chartCollections = [];

        for ($i = 6; $i >= 0; $i--) {
            $dayDate = Carbon::today()->subDays($i);
            $chartLabels[] = $dayDate->format('M d');

            $chartCashIn[] = (float) WalletTransaction::where('type', 'cash_in')
                ->where('status', 'completed')
                ->whereDate('updated_at', $dayDate)
                ->sum('amount');

            $chartCashOut[] = (float) WalletTransaction::whereIn('type', ['cash_out', 'collector_cashout'])
                ->where('status', 'completed')
                ->whereDate('updated_at', $dayDate)
                ->sum('amount');

            $chartCollections[] = (float) LoanPayment::whereDate('payment_date', $dayDate)->sum('amount_paid');
        }

        return view('host.dashboard.index', compact(
            'vaultBalance',
            'todayCashIn',
            'todayCashOut',
            'todayLoanCollections',
            'todaySavingsDeposits',
            'todayExpenses',
            'totalActiveLoans',
            'totalActiveClientsCount',
            'totalCollectorsCount',
            'totalSavingsActive',
            'totalPendingApprovals',
            'pendingLoanCount',
            'pendingWalletCount',
            'pendingCollectorCount',
            'pendingClientUpdatesCount',
            'recentTransactions',
            'chartLabels',
            'chartCashIn',
            'chartCashOut',
            'chartCollections'
        ));
    }
}
