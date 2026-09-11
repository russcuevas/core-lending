<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\Expense;
use Carbon\Carbon;

class HostReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        // Cash In
        $cashInTotal = WalletTransaction::where('type', 'cash_in')
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        // Cash Out
        $cashOutTotal = WalletTransaction::whereIn('type', ['cash_out', 'collector_cashout'])
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        // Loan Collections
        $loanPaymentsQuery = LoanPayment::whereBetween('payment_date', [$startDate, $endDate]);
        $loanCollectionsTotal = (clone $loanPaymentsQuery)->sum('amount_paid');
        $loanPrincipalTotal = (clone $loanPaymentsQuery)->sum('loan_premium_amount');
        $insuranceTotal = (clone $loanPaymentsQuery)->sum('insurance_premium_amount');

        // Savings Deposits
        $savingsTotal = SavingsAccount::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->sum('deposit_amount');

        // Expenses
        $expensesTotal = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');

        // Payments Breakdown
        $payments = LoanPayment::with(['client.user', 'collector.user', 'loan'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->latest()
            ->paginate(30);

        return view('host.reports.index', compact(
            'startDate',
            'endDate',
            'cashInTotal',
            'cashOutTotal',
            'loanCollectionsTotal',
            'loanPrincipalTotal',
            'insuranceTotal',
            'savingsTotal',
            'expensesTotal',
            'payments'
        ));
    }
}
