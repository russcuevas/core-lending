<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Host\HostDashboardController;
use App\Http\Controllers\Host\HostApprovalController;
use App\Http\Controllers\Host\HostAccountController;
use App\Http\Controllers\Host\HostTransactionController;
use App\Http\Controllers\Host\HostReportController;
use App\Http\Controllers\Host\HostSettingController;
use App\Http\Controllers\Admin\EncoderController;
use App\Http\Controllers\Admin\ReleasingController;
use App\Http\Controllers\Collector\CollectorController;
use App\Http\Controllers\Client\ClientController;

// Public & Authentication
Route::get('/', function () {
    if (Auth::check()) {
        return app(AuthController::class)->redirectBasedOnRole(Auth::user());
    }
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login/staff', [AuthController::class, 'loginStaff'])->name('login.staff');
Route::post('/login/client', [AuthController::class, 'loginClient'])->name('login.client');
Route::post('/login/reset-pin', [AuthController::class, 'requestPinReset'])->name('login.reset_pin');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 1. HOST / SUPERADMIN ROUTES
Route::middleware(['auth', 'role:host'])->prefix('host')->name('host.')->group(function () {
    Route::get('/dashboard', [HostDashboardController::class, 'index'])->name('dashboard');

    // Approvals
    Route::get('/approvals', [HostApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/mark-as-read', [HostApprovalController::class, 'markAsRead'])->name('approvals.mark_read');
    Route::post('/approvals/loans/{loan}/approve', [HostApprovalController::class, 'approveLoan'])->name('approvals.loans.approve');
    Route::post('/approvals/loans/{loan}/decline', [HostApprovalController::class, 'declineLoan'])->name('approvals.loans.decline');
    Route::post('/approvals/wallet/{transaction}/approve', [HostApprovalController::class, 'approveWalletTransaction'])->name('approvals.wallet.approve');
    Route::post('/approvals/wallet/{transaction}/decline', [HostApprovalController::class, 'declineWalletTransaction'])->name('approvals.wallet.decline');
    Route::post('/approvals/collectors/{user}/approve', [HostApprovalController::class, 'approveCollector'])->name('approvals.collectors.approve');
    Route::post('/approvals/collectors/{user}/decline', [HostApprovalController::class, 'declineCollector'])->name('approvals.collectors.decline');
    Route::post('/approvals/client-updates/{updateRequest}/approve', [HostApprovalController::class, 'approveClientUpdate'])->name('approvals.client_updates.approve');
    Route::post('/approvals/client-updates/{updateRequest}/decline', [HostApprovalController::class, 'declineClientUpdate'])->name('approvals.client_updates.decline');

    // Accounts & Credentials
    Route::get('/accounts', [HostAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts/staff', [HostAccountController::class, 'storeStaff'])->name('accounts.store_staff');
    Route::post('/accounts/{user}/reset-password', [HostAccountController::class, 'resetPassword'])->name('accounts.reset_password');
    Route::post('/accounts/{user}/reset-pin', [HostAccountController::class, 'resetPin'])->name('accounts.reset_pin');
    Route::post('/accounts/{user}/toggle-status', [HostAccountController::class, 'toggleStatus'])->name('accounts.toggle_status');
    Route::delete('/accounts/{user}', [HostAccountController::class, 'destroy'])->name('accounts.destroy');

    // Ledgers & Reports
    Route::get('/transactions', [HostTransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [HostTransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/adjust-balance', [HostTransactionController::class, 'adjustBalance'])->name('transactions.adjust_balance');
    Route::put('/transactions/{ledger}', [HostTransactionController::class, 'update'])->name('transactions.update');
    Route::get('/reports', [HostReportController::class, 'index'])->name('reports.index');

    // System Settings & Rates
    Route::get('/settings', [HostSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [HostSettingController::class, 'update'])->name('settings.update');
});

// 2. ADMIN ENCODER ROUTES
Route::middleware(['auth', 'role:admin_encoder,host'])->prefix('admin/encoder')->name('admin.encoder.')->group(function () {
    Route::get('/dashboard', [EncoderController::class, 'dashboard'])->name('dashboard');
    Route::get('/clients/create', [EncoderController::class, 'createClient'])->name('clients.create');
    Route::post('/clients', [EncoderController::class, 'storeClient'])->name('clients.store');
    Route::get('/clients', [EncoderController::class, 'clientList'])->name('clients.index');
    Route::get('/clients/{client}/print-card', [EncoderController::class, 'printClientQr'])->name('print_qr');
    Route::post('/clients/{client}/update-request', [EncoderController::class, 'requestClientUpdate'])->name('clients.update_request');
    Route::post('/clients/{client}/renew-loan', [EncoderController::class, 'renewLoan'])->name('clients.renew_loan');

    // Expenses
    Route::get('/expenses', [EncoderController::class, 'expensesIndex'])->name('expenses.index');
    Route::post('/expenses', [EncoderController::class, 'storeExpense'])->name('expenses.store');

    // Collectors Registration
    Route::get('/collectors/create', [EncoderController::class, 'createCollector'])->name('collectors.create');
    Route::post('/collectors', [EncoderController::class, 'storeCollector'])->name('collectors.store');

    // Daily Payments Print
    Route::get('/reports/daily-payments', [EncoderController::class, 'printDailyPayments'])->name('daily_payments');
});

// 3. ADMIN RELEASING OFFICER ROUTES
Route::middleware(['auth', 'role:admin_releasing,host'])->prefix('admin/releasing')->name('admin.releasing.')->group(function () {
    Route::get('/dashboard', [ReleasingController::class, 'dashboard'])->name('dashboard');
    Route::post('/requests/{transaction}/review', [ReleasingController::class, 'submitReview'])->name('requests.review');
    Route::post('/requests/{transaction}/execute', [ReleasingController::class, 'executeDisbursement'])->name('requests.execute');
    Route::post('/loans/{loan}/disburse', [ReleasingController::class, 'executeLoanRelease'])->name('loans.disburse');
});

// 4. COLLECTOR ROUTES
Route::middleware(['auth', 'role:collector'])->prefix('collector')->name('collector.')->group(function () {
    Route::get('/dashboard', [CollectorController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan', [CollectorController::class, 'scanQr'])->name('scan_qr');
    Route::get('/payments/collect', [CollectorController::class, 'collectPaymentForm'])->name('payments.collect_form');
    Route::post('/loans/{loan}/pay', [CollectorController::class, 'processPayment'])->name('payments.process');
    Route::post('/cashout', [CollectorController::class, 'requestCashout'])->name('cashout');
});

// 5. CLIENT ROUTES
Route::middleware(['auth', 'role:client'])->prefix('client')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');
    Route::post('/cash-in', [ClientController::class, 'requestCashIn'])->name('cash_in');
    Route::post('/cash-out', [ClientController::class, 'requestCashOut'])->name('cash_out');
    Route::post('/request-renewal', [ClientController::class, 'requestRenewal'])->name('request_renewal');
    Route::post('/savings', [ClientController::class, 'createSavings'])->name('savings.store');
    Route::post('/savings/{savings}/mature', [ClientController::class, 'matureSavings'])->name('savings.mature');
    Route::post('/change-pin', [ClientController::class, 'changePin'])->name('change_pin');
});
