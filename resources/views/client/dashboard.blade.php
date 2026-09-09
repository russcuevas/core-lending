@extends('layouts.app')

@section('title', 'Client Portal')
@section('page_title', 'Client Portal - My Lending Account')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/client.css') }}">
@endpush

@section('content')
    <!-- Client Hero Card -->
    <div class="client-hero-card">
        <div>
            <div style="font-size: 12px; color: #ffffff; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600;">Available Wallet Balance</div>
            <div style="font-size: 32px; font-weight: 800; font-family: var(--font-heading); color: #ffffff; margin: 3px 0; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25); font-variant-numeric: tabular-nums;">
                ₱{{ number_format($client->wallet_balance, 2) }}
            </div>
            <div style="font-size: 13px; color: #ffffff; opacity: 0.95; font-weight: 500;">Welcome, {{ $client->user->name }} ({{ $client->user->phone_number }})</div>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-emerald" onclick="openModal('cashInModal')">
                📥 Cash In
            </button>
            <button type="button" class="btn btn-amber" onclick="openModal('cashOutModal')">
                📤 Cash Out
            </button>
            <button type="button" class="btn btn-primary" style="background: #0284c7; border: none;" onclick="openModal('addSavingsModal')">
                🐖 Add Savings ({{ $savingsInterestRate }}%)
            </button>
            <button type="button" class="btn" style="background: rgba(255,255,255,0.18); color: #ffffff; border: 1px solid rgba(255,255,255,0.35); backdrop-filter: blur(4px);" onclick="openModal('changePinModal')">
                🔐 Change PIN
            </button>
        </div>
    </div>

    <!-- Show QR Code Toggle Card -->
    <div class="client-qr-toggle-box">
        <div class="client-qr-header">
            <div class="client-qr-info">
                <h3 class="client-qr-title">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--brand-blue);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    My Unique Client QR Code
                </h3>
                <p class="client-qr-subtitle">
                    Present this QR code to your collector during daily payments or transactions.
                </p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="qr-toggle-btn" onclick="toggleClientQr()">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                <span>Show QR Code</span>
            </button>
        </div>

        <div class="client-qr-image-display" id="client-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($client->qr_code_token) }}" alt="My QR Code" style="width: 100%; max-width: 160px; height: auto; margin: 0 auto; display: block;">
            <div style="font-size: 12px; font-family: monospace; font-weight: 700; margin-top: 8px; color: var(--brand-navy); background: #f1f5f9; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                {{ $client->qr_code_token }}
            </div>
        </div>
    </div>

    <!-- Active Loan Section -->
    @if($activeLoan)
        @php
            $totalTermDays = $loanSchedules->count() > 0 ? $loanSchedules->count() : 60;
            $progressPercent = min(100, round(($paidDaysCount / $totalTermDays) * 100));
            $extendedDaysCount = max(0, $totalTermDays - 60);
        @endphp

        <!-- Delinquency & Overdue Alerts -->
        @if($missedPastDuesCount >= 3)
            <div style="background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 12px; box-shadow: 0 2px 6px rgba(225, 29, 72, 0.08);">
                <div style="font-size: 24px; line-height: 1;">⚠️</div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #9f1239; font-size: 14px; margin-bottom: 3px;">
                        Overdue Warning: {{ $missedPastDuesCount }} Accumulative Unpaid Days
                    </div>
                    <p style="margin: 0; font-size: 12.5px; color: #be123c; line-height: 1.45;">
                        You have missed <strong>{{ $missedPastDuesCount }} daily payment(s)</strong> totaling <strong>₱{{ number_format($missedPastDuesCount * $activeLoan->daily_installment, 2) }}</strong>. 
                        Additional extension days (<strong>Day 61+</strong>) have been automatically added to your repayment schedule to give you extra time to settle your unpaid dues. Please coordinate immediately with your assigned collector to avoid account penalties.
                    </p>
                </div>
            </div>
        @elseif($missedPastDuesCount > 0)
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                <div style="font-size: 20px; line-height: 1;">ℹ️</div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #92400e; font-size: 13.5px; margin-bottom: 2px;">
                        Repayment Schedule Extended ({{ $extendedDaysCount }} Extra Day{{ $extendedDaysCount > 1 ? 's' : '' }})
                    </div>
                    <p style="margin: 0; font-size: 12px; color: #b45309; line-height: 1.4;">
                        Because you have {{ $missedPastDuesCount }} unpaid past daily installment(s), an extra {{ $extendedDaysCount }} day(s) (Day 61{{ $extendedDaysCount > 1 ? ' to Day ' . (60 + $extendedDaysCount) : '' }}) has been added to your schedule so you can pay your missed dates without shortening your term.
                    </p>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📄 My Active Loan (#{{ $activeLoan->id }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        {{ $totalTermDays }}-Day Term Repayment Plan
                        @if($extendedDaysCount > 0)
                            <span style="color: #d97706; font-weight: 600;">(Extended by {{ $extendedDaysCount }} day{{ $extendedDaysCount > 1 ? 's' : '' }} due to unpaid dates)</span>
                        @endif
                    </p>
                </div>
                <span class="badge badge-emerald">{{ ucfirst($activeLoan->status) }}</span>
            </div>

            <!-- Loan Progress Tracker -->
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; flex-wrap: wrap; gap: 4px;">
                    <span>Repayment Progress</span>
                    <span style="color: #059669;">
                        {{ $paidDaysCount }} / {{ $totalTermDays }} Days Completed ({{ $progressPercent }}%)
                        @if($extendedDaysCount > 0)
                            <span class="badge badge-amber" style="font-size: 11px; margin-left: 4px;">+{{ $extendedDaysCount }} Extended Day{{ $extendedDaysCount > 1 ? 's' : '' }}</span>
                        @endif
                    </span>
                </div>
                <div class="loan-progress-container">
                    <div class="loan-progress-bar" style="width: {{ $progressPercent }}%;"></div>
                </div>
            </div>

            <div class="stats-grid" style="margin-bottom: 16px;">
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Principal Amount</div>
                        <div class="stat-value">₱{{ number_format($activeLoan->principal_amount, 2) }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Total Payable</div>
                        <div class="stat-value">₱{{ number_format($activeLoan->total_payable, 2) }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Daily Installment</div>
                        <div class="stat-value" style="color: #d97706;">₱{{ number_format($activeLoan->daily_installment, 2) }}</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <div class="stat-label">Remaining Balance</div>
                        <div class="stat-value" style="color: #059669;">₱{{ number_format($activeLoan->remaining_balance, 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Payment Schedule & Receipts -->
            <h4 style="font-size: 14.5px; margin-bottom: 10px;">
                {{ $totalTermDays }}-Day Payment History & Scheduled Dues
                @if($extendedDaysCount > 0)
                    <span class="badge badge-amber" style="font-size: 11px; font-weight: 600; margin-left: 6px;">Includes {{ $extendedDaysCount }} Extra Extension Day(s)</span>
                @endif
            </h4>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Day #</th>
                            <th>Due Date</th>
                            <th>Expected Amount</th>
                            <th>Amount Paid</th>
                            <th>Status</th>
                            <th>Paid Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loanSchedules as $schedule)
                            @php
                                $isPastDue = \Carbon\Carbon::parse($schedule->due_date)->isPast() && !\Carbon\Carbon::parse($schedule->due_date)->isToday();
                                $isToday = \Carbon\Carbon::parse($schedule->due_date)->isToday();
                                $isExtended = $schedule->day_number > 60;
                            @endphp
                            <tr @if($isExtended) style="background: rgba(245, 158, 11, 0.04);" @endif>
                                <td>
                                    <strong>Day {{ $schedule->day_number }}</strong>
                                    @if($isExtended)
                                        <span class="badge badge-amber" style="font-size: 10px; padding: 2px 6px; margin-left: 4px;">Extra Day</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $schedule->due_date }}
                                    @if($isToday)
                                        <span class="badge badge-primary" style="font-size: 10px; padding: 1px 5px; margin-left: 2px;">Today</span>
                                    @endif
                                </td>
                                <td>₱{{ number_format($schedule->expected_amount, 2) }}</td>
                                <td style="font-weight: 700; color: {{ $schedule->paid_amount > 0 ? '#059669' : 'inherit' }};">
                                    {{ $schedule->paid_amount > 0 ? '₱' . number_format($schedule->paid_amount, 2) : '-' }}
                                </td>
                                <td>
                                    @if($schedule->status === 'paid')
                                        <span class="badge badge-emerald">✓ Paid</span>
                                    @elseif($schedule->status === 'partial')
                                        <span class="badge badge-amber">Partial (₱{{ number_format($schedule->paid_amount, 2) }})</span>
                                    @elseif($isPastDue)
                                        <span class="badge badge-rose">⚠️ Unpaid / Missed</span>
                                    @else
                                        <span class="badge badge-slate">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $schedule->paid_at ? \Carbon\Carbon::parse($schedule->paid_at)->format('M d, Y h:i A') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
    @elseif($activeLoan && in_array($activeLoan->status, ['pending_host_approval', 'approved_for_release', 'ready_for_release', 'pending_releasing_review']))
        <div class="card" style="border-left: 4px solid #f59e0b;">
            <div class="card-header">
                <h3 class="card-title">⏳ Loan Renewal Application Under Review</h3>
                <span class="badge badge-amber">Pending Host Approval</span>
            </div>
            <div style="padding: 18px; font-size: 13px; color: var(--text-secondary);">
                Your loan renewal of <strong style="color: #059669;">₱{{ number_format($activeLoan->principal_amount, 2) }}</strong> has been submitted and is waiting for Superadmin approval and disbursement.
            </div>
        </div>
    @else
        <div class="card" style="border-left: 4px solid #059669; background: #f0fdf4;">
            <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #065f46; margin: 0 0 4px 0;">🎉 You are eligible for a Loan Renewal (Re-Loan)!</h3>
                    <p style="margin: 0; font-size: 12.5px; color: #047857;">You currently have no active loan. Apply for a new loan cycle anytime.</p>
                </div>
                <button type="button" class="btn btn-emerald" onclick="openModal('applyRenewalModal')" style="font-weight: 700;">
                    🔄 Apply for Loan Renewal
                </button>
            </div>
        </div>
    @endif

    <!-- Savings Funds Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🐖 My Active Savings Funds</h3>
            <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addSavingsModal')">
                + Open New Savings Fund ({{ $savingsInterestRate }}%)
            </button>
        </div>

        @if($savingsAccounts->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary); font-size: 13px;">
                You currently have no active savings fund. Open one today to earn {{ $savingsInterestRate }}% interest locked for {{ $savingsLockInDays }} days with daily interest credited to your wallet!
            </div>
        @else
            <div class="host-approval-grid">
                @foreach($savingsAccounts as $sav)
                    <div class="approval-card" style="border-left-color: #059669;">
                        <div>
                            <div class="approval-header">
                                <h4 style="font-size: 15px;">Deposit: ₱{{ number_format($sav->deposit_amount, 2) }}</h4>
                                <span class="badge badge-emerald">{{ number_format($sav->interest_rate_percent, 1) }}% / {{ $sav->lock_in_days }} Days</span>
                            </div>

                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Daily Interest Payout</span>
                                <span class="approval-meta-val" style="color: #059669;">+₱{{ number_format($sav->daily_interest_amount, 2) }} / day</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Total Expected Interest</span>
                                <span class="approval-meta-val">₱{{ number_format($sav->total_expected_interest, 2) }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Maturity Date</span>
                                <span class="approval-meta-val">{{ $sav->maturity_date }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Status</span>
                                <span class="approval-meta-val"><span class="badge badge-emerald">{{ ucfirst($sav->status) }}</span></span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Wallet Transaction History -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💳 Recent Wallet Transactions & Cash In/Out Requests</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Releasing Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$walletTransactions->isEmpty())
                        @foreach($walletTransactions as $wTx)
                            <tr>
                                <td>{{ $wTx->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <span class="badge {{ $wTx->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}">
                                        {{ strtoupper(str_replace('_', ' ', $wTx->type)) }}
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: {{ $wTx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                                    {{ $wTx->type === 'cash_in' ? '+' : '-' }}₱{{ number_format($wTx->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $wTx->status === 'completed' ? 'badge-emerald' : ($wTx->status === 'declined' ? 'badge-rose' : 'badge-amber') }}">
                                        {{ str_replace('_', ' ', $wTx->status) }}
                                    </span>
                                </td>
                                <td>{{ $wTx->releasing_notes ?? ($wTx->decline_reason ?? '-') }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 20px; color: var(--text-muted);">No wallet transactions yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cash In Request Modal -->
    <div class="modal-overlay" id="cashInModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Request Cash In to Wallet</h3>
                <button type="button" class="modal-close" onclick="closeModal('cashInModal')">&times;</button>
            </div>
            <form action="{{ route('client.cash_in') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                        Enter the amount you wish to Cash In. A releasing officer or collector will be assigned to collect the cash and credit your balance.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Cash In Amount (₱) *</label>
                        <input type="number" step="0.01" min="100" name="amount" class="form-control" placeholder="e.g. 3000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Preferred collection time or instructions..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('cashInModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Submit Cash In Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cash Out Request Modal -->
    <div class="modal-overlay" id="cashOutModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Request Cash Out / Withdrawal</h3>
                <button type="button" class="modal-close" onclick="closeModal('cashOutModal')">&times;</button>
            </div>
            <form action="{{ route('client.cash_out') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px;">
                        <div style="font-size: 11.5px; color: var(--text-secondary);">Current Available Balance:</div>
                        <div style="font-size: 20px; font-weight: 700; color: #059669;">₱{{ number_format($client->wallet_balance, 2) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cash Out Amount (₱) *</label>
                        <input type="number" step="0.01" min="100" max="{{ $client->wallet_balance }}" name="amount" class="form-control" placeholder="e.g. 1000" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Preferred release schedule..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('cashOutModal')">Cancel</button>
                    <button type="submit" class="btn btn-amber">Submit Cash Out Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Savings Fund Modal -->
    <div class="modal-overlay" id="addSavingsModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Open {{ $savingsLockInDays }}-Day {{ $savingsInterestRate }}% Savings Fund</h3>
                <button type="button" class="modal-close" onclick="closeModal('addSavingsModal')">&times;</button>
            </div>
            <form action="{{ route('client.savings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="savings-plan-card" style="margin-bottom: 14px;">
                        <span class="savings-rate-tag">{{ $savingsInterestRate }}% GUARANTEED RETURN</span>
                        <h4 style="font-size: 15px; margin-bottom: 4px;">{{ $savingsLockInDays }}-Day Fixed Lock-in Growth</h4>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">
                            Daily interest is automatically calculated and credited directly to your wallet balance every single day!
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Savings Deposit Amount (₱) *</label>
                        <input type="number" step="0.01" min="500" max="{{ $client->wallet_balance }}" name="deposit_amount" id="savings_deposit_amount" data-rate="{{ $savingsInterestRate }}" data-days="{{ $savingsLockInDays }}" class="form-control" placeholder="e.g. 10000" required oninput="previewSavingsCalculation()">
                        <div class="form-hint">Deducted from your current wallet balance (₱{{ number_format($client->wallet_balance, 2) }}).</div>
                    </div>

                    <!-- Live Calculation Preview -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); font-size: 12.5px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-secondary);">Daily Interest Credited to Wallet:</span>
                            <strong style="color: #059669;" id="preview_daily_interest">₱0.00 / day</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-secondary);">Total {{ $savingsLockInDays }}-Day Earned Interest:</span>
                            <strong id="preview_total_interest">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-color); padding-top: 5px;">
                            <span style="font-weight: 600;">Total Payout at Maturity:</span>
                            <strong style="color: #059669; font-size: 14px;" id="preview_total_maturity">₱0.00</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addSavingsModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Activate Savings Deposit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change PIN Modal -->
    <div class="modal-overlay" id="changePinModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">🔐 Change 4-Digit Security PIN</h3>
                <button type="button" class="modal-close" onclick="closeModal('changePinModal')">&times;</button>
            </div>
            <form action="{{ route('client.change_pin') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                        Enter your current 4-digit PIN and your new 4-digit PIN to update your account security credentials.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Current 4-Digit PIN *</label>
                        <input type="password" name="current_pin" class="form-control" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required autocomplete="current-password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New 4-Digit PIN *</label>
                        <input type="password" name="new_pin" class="form-control" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required autocomplete="new-password">
                        <div class="form-hint">Must be exactly 4 numerical digits.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New PIN *</label>
                        <input type="password" name="new_pin_confirmation" class="form-control" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('changePinModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Security PIN</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Apply for Loan Renewal Modal -->
    <div class="modal-overlay" id="applyRenewalModal">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">🔄 Apply for Loan Renewal (Re-Loan)</h3>
                    <div style="font-size: 12px; color: var(--text-secondary);">Request a new loan cycle under verified terms.</div>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('applyRenewalModal')">&times;</button>
            </div>
            <form action="{{ route('client.request_renewal') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: rgba(5, 150, 105, 0.08); border: 1px solid rgba(5, 150, 105, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: #047857; margin-bottom: 4px;">📌 Verified System Terms:</div>
                        <div style="display: flex; gap: 16px; font-size: 12.5px; color: var(--text-primary);">
                            <div><strong>Interest:</strong> {{ $loanInterestRate ?? 10 }}%</div>
                            <div><strong>Term:</strong> {{ $loanTermDays ?? 60 }} Days (Daily)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Requested Loan Amount (₱) <span style="color:red;">*</span></label>
                        <input type="number" step="100" min="500" name="amount" id="client_renew_principal" class="form-control" placeholder="Enter amount, e.g. 10000" required oninput="calculateClientRenewLoan()">
                        
                        <!-- Quick Presets -->
                        <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setClientRenewAmount(5000)">₱5,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setClientRenewAmount(10000)">₱10,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setClientRenewAmount(15000)">₱15,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setClientRenewAmount(20000)">₱20,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setClientRenewAmount(30000)">₱30,000</button>
                        </div>
                    </div>

                    <!-- Live Breakdown Preview -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Estimated Computation</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Interest ({{ $loanInterestRate ?? 10 }}%)</span>
                                <span style="font-size: 14px; font-weight: 700; color: #d97706;" id="client_renew_interest">₱0.00</span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Payable</span>
                                <span style="font-size: 14px; font-weight: 800; color: #059669;" id="client_renew_payable">₱0.00</span>
                            </div>
                            <div style="grid-column: span 2; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 4px;">
                                <span style="font-size: 12px; color: var(--text-secondary);">Daily Installment: </span>
                                <strong style="font-size: 16px; color: #0284c7;" id="client_renew_daily">₱0.00 / day</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Additional capital for sari-sari store expansion"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('applyRenewalModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">Submit Renewal Request &rarr;</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/client.js') }}"></script>
    <script>
        const clientLoanRate = {{ (float)($loanInterestRate ?? 10) }};
        const clientLoanTerm = {{ (int)($loanTermDays ?? 60) }};

        function setClientRenewAmount(val) {
            document.getElementById('client_renew_principal').value = val;
            calculateClientRenewLoan();
        }

        function calculateClientRenewLoan() {
            const principal = parseFloat(document.getElementById('client_renew_principal').value) || 0;
            const interest = principal * (clientLoanRate / 100);
            const total = principal + interest;
            const daily = clientLoanTerm > 0 ? (total / clientLoanTerm) : 0;

            document.getElementById('client_renew_interest').innerText = '₱' + interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('client_renew_payable').innerText = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('client_renew_daily').innerText = '₱' + daily.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' / day';
        }
    </script>
@endpush
