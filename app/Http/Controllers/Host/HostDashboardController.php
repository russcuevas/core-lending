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
use App\Models\CashTurnover;
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

        $todayPayments = LoanPayment::whereDate('payment_date', $today)->get();
        $todayLoanCollections = $todayPayments->sum('amount_paid');
        $todayLoanPremium = $todayPayments->sum('loan_premium_amount');
        $todayInsurancePremium = $todayPayments->sum('insurance_premium_amount');

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
        $pendingTurnoversCount = CashTurnover::where('status', 'pending_host_approval')->count();

        $totalPendingApprovals = $pendingLoanCount + $pendingWalletCount + $pendingCollectorCount + $pendingClientUpdatesCount + $pendingTurnoversCount;

        // 4. Daily Collections per Agent (Banggaan Table for Superadmin)
        $collectors = Collector::with(['user', 'assignedClients.user'])->get();
        $agentCollections = $collectors->map(function ($col) use ($today) {
            $colPayments = LoanPayment::where('collector_id', $col->id)
                ->whereDate('payment_date', $today)
                ->get();

            $totalCollected = (float) $colPayments->sum('amount_paid');
            $processingAmount = (float) $colPayments->where('status', 'processing')->sum('amount_paid');
            $remittedAmount = (float) $colPayments->whereIn('status', ['paid', 'remitted', 'completed'])->sum('amount_paid');

            $status = 'no_collections';
            if ($totalCollected > 0) {
                if ($processingAmount > 0 && $remittedAmount > 0) {
                    $status = 'partial_remitted';
                } elseif ($processingAmount > 0) {
                    $status = 'pending_remittance';
                } else {
                    $status = 'remitted';
                }
            }

            return [
                'collector' => $col,
                'collector_name' => $col->user->name ?? 'Collector',
                'name' => $col->user->name ?? 'Collector',
                'phone' => $col->user->phone_number ?? 'N/A',
                'area' => $col->assigned_area ?? 'General Area',
                'clients_collected_count' => $colPayments->count(),
                'total_collected' => $totalCollected,
                'loan_premium' => (float) $colPayments->sum('loan_premium_amount'),
                'insurance_premium' => (float) $colPayments->sum('insurance_premium_amount'),
                'processing_amount' => $processingAmount,
                'remitted_amount' => $remittedAmount,
                'status' => $status,
            ];
        });

        // 5. Recent transactions & Turnovers
        $recentTransactions = HostVaultLedger::with('creator')->latest()->take(10)->get();

        // 6. Weekly Chart data (last 7 days)
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
            'todayLoanPremium',
            'todayInsurancePremium',
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
            'pendingTurnoversCount',
            'agentCollections',
            'recentTransactions',
            'chartLabels',
            'chartCashIn',
            'chartCashOut',
            'chartCollections'
        ));
    }
}
