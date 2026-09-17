<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\SavingsAccount;
use App\Models\WalletTransaction;
use App\Models\Expense;
use App\Models\HostVaultLedger;
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

        // Cash Out (Disbursed Loans + Wallet Cash-outs + Savings Payouts)
        $loanReleasesTotal = Loan::whereNotNull('release_date')
            ->whereBetween('release_date', [$startDate, $endDate])
            ->whereNotIn('status', ['rejected', 'pending_host_approval', 'approved_for_release'])
            ->sum('principal_amount');

        $walletCashOutTotal = WalletTransaction::whereIn('type', ['cash_out', 'collector_cashout'])
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        $savingsPayoutTotal = HostVaultLedger::where('type', 'out')
            ->where('category', 'savings_payout')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        $cashOutTotal = $loanReleasesTotal + $walletCashOutTotal + $savingsPayoutTotal;

        // Loan Collections
        $loanPaymentsQuery = LoanPayment::whereBetween('payment_date', [$startDate, $endDate]);
        $loanCollectionsTotal = (clone $loanPaymentsQuery)->sum('amount_paid');
        $loanPrincipalTotal = (clone $loanPaymentsQuery)->sum('loan_premium_amount');
        $insuranceTotal = (clone $loanPaymentsQuery)->sum('insurance_premium_amount');

        // Savings Deposits & Interest
        $savingsQuery = SavingsAccount::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $savingsTotal = (clone $savingsQuery)->sum('deposit_amount');
        $savingsExpectedInterestTotal = (clone $savingsQuery)->sum('total_expected_interest');
        $savingsInterestAccumulated = (clone $savingsQuery)->sum('accumulated_interest_paid');
        $dailyInterestInPeriod = WalletTransaction::where('type', 'daily_interest')
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');
        $savingsInterestTotal = max((float) $savingsInterestAccumulated, (float) $dailyInterestInPeriod);

        // Expenses
        $expensesTotal = Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $allTimeExpensesTotal = Expense::sum('amount');

        // Total Client Wallet (all clients)
        $totalClientWallet = (float) \App\Models\Client::sum('wallet_balance');

        // Total Vault Savings (Active savings fund deposits)
        $totalVaultSavings = (float) SavingsAccount::where('status', 'active')->sum('deposit_amount');
        if ($totalVaultSavings <= 0) {
            $totalVaultSavings = (float) SavingsAccount::sum('deposit_amount');
        }

        // Profit & Loss Formula Calculation:
        // Income = Actual Money Vault + Loan Premium Balance (all client) + Insurance Premium Balance
        $latestLedger = HostVaultLedger::latest('id')->first();
        $actualMoneyVault = $latestLedger ? (float) $latestLedger->vault_balance_after : 1000000.00;

        $loanPremiumBalanceAll = (float) Loan::where('status', 'active')->sum('remaining_balance');

        $activeLoans = Loan::where('status', 'active')->get();
        $insurancePremiumBalanceAll = (float) $activeLoans->sum(function ($l) {
            return (float) ($l->remaining_insurance ?? 0);
        });
        if ($insurancePremiumBalanceAll <= 0) {
            $insurancePremiumBalanceAll = (float) LoanPayment::where('status', 'paid')->sum('insurance_premium_amount');
        }

        $totalIncome = $actualMoneyVault + $loanPremiumBalanceAll + $insurancePremiumBalanceAll;

        // Expense = Office Expense + Savings Interest (all client)
        $allClientsSavingsInterest = (float) SavingsAccount::sum('accumulated_interest_paid');
        if ($allClientsSavingsInterest <= 0) {
            $allClientsSavingsInterest = (float) WalletTransaction::where('type', 'daily_interest')->where('status', 'completed')->sum('amount');
        }

        $totalExpense = $allTimeExpensesTotal + $allClientsSavingsInterest;
        $profitAndLoss = $totalIncome - $totalExpense;

        // Payments Breakdown
        $payments = LoanPayment::with(['client.user', 'collector.user', 'loan.schedules'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->latest()
            ->paginate(30);

        return view('host.reports.index', compact(
            'startDate',
            'endDate',
            'cashInTotal',
            'cashOutTotal',
            'loanReleasesTotal',
            'walletCashOutTotal',
            'savingsPayoutTotal',
            'loanCollectionsTotal',
            'loanPrincipalTotal',
            'insuranceTotal',
            'savingsTotal',
            'savingsInterestTotal',
            'savingsExpectedInterestTotal',
            'expensesTotal',
            'allTimeExpensesTotal',
            'totalClientWallet',
            'totalVaultSavings',
            'actualMoneyVault',
            'loanPremiumBalanceAll',
            'insurancePremiumBalanceAll',
            'totalIncome',
            'allClientsSavingsInterest',
            'totalExpense',
            'profitAndLoss',
            'payments'
        ));
    }

    public function print(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today()->format('Y-m-d'));

        // Cash In
        $cashInTotal = WalletTransaction::where('type', 'cash_in')
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        // Cash Out (Disbursed Loans + Wallet Cash-outs + Savings Payouts)
        $loanReleasesTotal = Loan::whereNotNull('release_date')
            ->whereBetween('release_date', [$startDate, $endDate])
            ->whereNotIn('status', ['rejected', 'pending_host_approval', 'approved_for_release'])
            ->sum('principal_amount');

        $walletCashOutTotal = WalletTransaction::whereIn('type', ['cash_out', 'collector_cashout'])
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        $savingsPayoutTotal = HostVaultLedger::where('type', 'out')
            ->where('category', 'savings_payout')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');

        $cashOutTotal = $loanReleasesTotal + $walletCashOutTotal + $savingsPayoutTotal;

        // Loan Collections
        $loanPaymentsQuery = LoanPayment::whereBetween('payment_date', [$startDate, $endDate]);
        $loanCollectionsTotal = (clone $loanPaymentsQuery)->sum('amount_paid');
        $loanPrincipalTotal = (clone $loanPaymentsQuery)->sum('loan_premium_amount');
        $insuranceTotal = (clone $loanPaymentsQuery)->sum('insurance_premium_amount');

        // Savings Deposits & Interest
        $savingsQuery = SavingsAccount::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $savingsTotal = (clone $savingsQuery)->sum('deposit_amount');
        $savingsExpectedInterestTotal = (clone $savingsQuery)->sum('total_expected_interest');
        $savingsInterestAccumulated = (clone $savingsQuery)->sum('accumulated_interest_paid');
        $dailyInterestInPeriod = WalletTransaction::where('type', 'daily_interest')
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum('amount');
        $savingsInterestTotal = max((float) $savingsInterestAccumulated, (float) $dailyInterestInPeriod);

        // Expenses
        $expenses = Expense::with('user')->whereBetween('date', [$startDate, $endDate])->orderBy('date', 'asc')->get();
        $expensesTotal = $expenses->sum('amount');
        $allTimeExpensesTotal = Expense::sum('amount');

        // Total Client Wallet
        $totalClientWallet = (float) \App\Models\Client::sum('wallet_balance');

        // Total Vault Savings
        $totalVaultSavings = (float) SavingsAccount::where('status', 'active')->sum('deposit_amount');
        if ($totalVaultSavings <= 0) {
            $totalVaultSavings = (float) SavingsAccount::sum('deposit_amount');
        }

        // Profit & Loss Formula Calculation:
        $latestLedger = HostVaultLedger::latest('id')->first();
        $actualMoneyVault = $latestLedger ? (float) $latestLedger->vault_balance_after : 1000000.00;

        $loanPremiumBalanceAll = (float) Loan::where('status', 'active')->sum('remaining_balance');

        $activeLoans = Loan::where('status', 'active')->get();
        $insurancePremiumBalanceAll = (float) $activeLoans->sum(function ($l) {
            return (float) ($l->remaining_insurance ?? 0);
        });
        if ($insurancePremiumBalanceAll <= 0) {
            $insurancePremiumBalanceAll = (float) LoanPayment::where('status', 'paid')->sum('insurance_premium_amount');
        }

        $totalIncome = $actualMoneyVault + $loanPremiumBalanceAll + $insurancePremiumBalanceAll;

        $allClientsSavingsInterest = (float) SavingsAccount::sum('accumulated_interest_paid');
        if ($allClientsSavingsInterest <= 0) {
            $allClientsSavingsInterest = (float) WalletTransaction::where('type', 'daily_interest')->where('status', 'completed')->sum('amount');
        }

        $totalExpense = $allTimeExpensesTotal + $allClientsSavingsInterest;
        $profitAndLoss = $totalIncome - $totalExpense;

        // All Payments in Period
        $payments = LoanPayment::with(['client.user', 'collector.user', 'loan.schedules'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'asc')
            ->get();

        return view('host.reports.print', compact(
            'startDate',
            'endDate',
            'cashInTotal',
            'cashOutTotal',
            'loanReleasesTotal',
            'walletCashOutTotal',
            'savingsPayoutTotal',
            'loanCollectionsTotal',
            'loanPrincipalTotal',
            'insuranceTotal',
            'savingsTotal',
            'savingsInterestTotal',
            'savingsExpectedInterestTotal',
            'expensesTotal',
            'expenses',
            'allTimeExpensesTotal',
            'totalClientWallet',
            'totalVaultSavings',
            'actualMoneyVault',
            'loanPremiumBalanceAll',
            'insurancePremiumBalanceAll',
            'totalIncome',
            'allClientsSavingsInterest',
            'totalExpense',
            'profitAndLoss',
            'payments'
        ));
    }
}
