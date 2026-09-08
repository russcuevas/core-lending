@extends('layouts.app')

@section('title', 'Client Portal')
@section('page_title', 'Client Portal - My Lending Account')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/client.css') }}">
@endpush

@section('content')
    <!-- Client Hero Card -->
    <div class="client-hero-card">
        <div>
            <div style="font-size: 13px; color: #ffffff; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600;">Available Wallet Balance</div>
            <div style="font-size: 38px; font-weight: 800; font-family: var(--font-heading); color: #ffffff; margin: 4px 0; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);">
                ₱{{ number_format($client->wallet_balance, 2) }}
            </div>
            <div style="font-size: 13.5px; color: #ffffff; opacity: 0.95; font-weight: 500;">Welcome, {{ $client->user->name }} ({{ $client->user->phone_number }})</div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-emerald" onclick="openModal('cashInModal')">
                📥 Cash In
            </button>
            <button type="button" class="btn btn-amber" onclick="openModal('cashOutModal')">
                📤 Cash Out
            </button>
            <button type="button" class="btn btn-primary" style="background: #3b82f6; border: none;" onclick="openModal('addSavingsModal')">
                🐖 Add Savings Fund (10%)
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
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                <span>Show QR Code</span>
            </button>
        </div>

        <div class="client-qr-image-display" id="client-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($client->qr_code_token) }}" alt="My QR Code" style="width: 100%; max-width: 180px; height: auto; margin: 0 auto; display: block;">
            <div style="font-size: 13px; font-family: monospace; font-weight: 700; margin-top: 10px; color: var(--brand-navy); background: #f1f5f9; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                {{ $client->qr_code_token }}
            </div>
        </div>
    </div>

    <!-- Active Loan Section -->
    @if($activeLoan)
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📄 My Active Loan (#{{ $activeLoan->id }})</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        60-Day Fixed Term Loan Repayment Plan
                    </p>
                </div>
                <span class="badge badge-emerald">{{ ucfirst($activeLoan->status) }}</span>
            </div>

            <!-- Loan Progress Tracker -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600;">
                    <span>Repayment Progress</span>
                    <span style="color: #059669;">{{ $paidDaysCount }} / 60 Days Completed ({{ round(($paidDaysCount / 60) * 100) }}%)</span>
                </div>
                <div class="loan-progress-container">
                    <div class="loan-progress-bar" style="width: {{ ($paidDaysCount / 60) * 100 }}%;"></div>
                </div>
            </div>

            <div class="stats-grid" style="margin-bottom: 20px;">
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

            <!-- 60-Day Payment Schedule & Receipts -->
            <h4 style="font-size: 15px; margin-bottom: 12px;">60-Day Payment History & Scheduled Dues</h4>
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
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
                            <tr>
                                <td><strong>Day {{ $schedule->day_number }}</strong></td>
                                <td>{{ $schedule->due_date }}</td>
                                <td>₱{{ number_format($schedule->expected_amount, 2) }}</td>
                                <td style="font-weight: 700; color: {{ $schedule->paid_amount > 0 ? '#059669' : 'inherit' }};">
                                    {{ $schedule->paid_amount > 0 ? '₱' . number_format($schedule->paid_amount, 2) : '-' }}
                                </td>
                                <td>
                                    @if($schedule->status === 'paid')
                                        <span class="badge badge-emerald">✓ Paid</span>
                                    @elseif($schedule->status === 'partial')
                                        <span class="badge badge-amber">Partial</span>
                                    @else
                                        <span class="badge badge-slate">Unpaid</span>
                                    @endif
                                </td>
                                <td>{{ $schedule->paid_at ? \Carbon\Carbon::parse($schedule->paid_at)->format('M d, Y h:i A') : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Savings Funds Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🐖 My Active Savings Funds (10% 60-Day Lock-In)</h3>
            <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addSavingsModal')">
                + Open New Savings Fund
            </button>
        </div>

        @if($savingsAccounts->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                You currently have no active savings fund. Open one today to earn 10% interest locked for 60 days with daily interest credited to your wallet!
            </div>
        @else
            <div class="host-approval-grid">
                @foreach($savingsAccounts as $sav)
                    <div class="approval-card" style="border-left-color: #059669;">
                        <div>
                            <div class="approval-header">
                                <h4 style="font-size: 16px;">Deposit: ₱{{ number_format($sav->deposit_amount, 2) }}</h4>
                                <span class="badge badge-emerald">10% / 60 Days</span>
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
            <table class="data-table">
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
                    @forelse($walletTransactions as $wTx)
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
                    @empty
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 24px; color: var(--text-muted);">No wallet transactions yet.</td>
                        </tr>
                    @endforelse
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
                    <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
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
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px;">
                        <div style="font-size: 12px; color: var(--text-secondary);">Current Available Balance:</div>
                        <div style="font-size: 22px; font-weight: 700; color: #059669;">₱{{ number_format($client->wallet_balance, 2) }}</div>
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
                <h3 class="modal-title">Open 60-Day 10% Savings Fund</h3>
                <button type="button" class="modal-close" onclick="closeModal('addSavingsModal')">&times;</button>
            </div>
            <form action="{{ route('client.savings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="savings-plan-card" style="margin-bottom: 16px;">
                        <span class="savings-rate-tag">10% GUARANTEED RETURN</span>
                        <h4 style="font-size: 16px; margin-bottom: 6px;">60-Day Fixed Lock-in Growth</h4>
                        <p style="font-size: 12.5px; color: var(--text-secondary); margin: 0;">
                            Daily interest is automatically calculated and credited directly to your wallet balance every single day!
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Savings Deposit Amount (₱) *</label>
                        <input type="number" step="0.01" min="500" max="{{ $client->wallet_balance }}" name="deposit_amount" id="savings_deposit_amount" class="form-control" placeholder="e.g. 10000" required oninput="previewSavingsCalculation()">
                        <div class="form-hint">Deducted from your current wallet balance (₱{{ number_format($client->wallet_balance, 2) }}).</div>
                    </div>

                    <!-- Live Calculation Preview -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 14px; border-radius: var(--radius-sm); font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Daily Interest Credited to Wallet:</span>
                            <strong style="color: #059669;" id="preview_daily_interest">₱0.00 / day</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <span style="color: var(--text-secondary);">Total 60-Day Earned Interest:</span>
                            <strong id="preview_total_interest">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-color); padding-top: 6px;">
                            <span style="font-weight: 600;">Total Payout at Maturity:</span>
                            <strong style="color: #059669; font-size: 15px;" id="preview_total_maturity">₱0.00</strong>
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
@endsection

@push('scripts')
    <script src="{{ asset('js/client.js') }}"></script>
@endpush
