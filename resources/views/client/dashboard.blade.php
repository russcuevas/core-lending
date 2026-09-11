@extends('layouts.app')

@section('title', 'Client Portal')
@section('page_title', 'Client Portal - My Lending Account')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/client.css') }}">
@endpush

@section('content')
    <!-- 1. Hero Wallet Card -->
    <div class="client-hero-card">
        <div>
            <div class="hero-balance-label">Available Wallet Balance</div>
            <div class="hero-balance-val">
                ₱{{ number_format($client->wallet_balance, 2) }}
            </div>
            <div class="hero-user-info">
                <span>👋 Welcome, <strong>{{ $client->user->name }}</strong></span>
                <span style="opacity: 0.75;">•</span>
                <span style="opacity: 0.9;">📞 {{ $client->user->phone_number }}</span>
            </div>

            <!-- Dynamic Notification Status Chips -->
            @if ($incomingApprovedWalletTx->count() > 0 || $approvedLoanForRelease || $pendingUnderReviewWalletTx->count() > 0)
                <div class="hero-status-pills">
                    @if ($incomingApprovedWalletTx->count() > 0 || $approvedLoanForRelease)
                        @php
                            $totApproved =
                                $incomingApprovedWalletTx->sum('amount') +
                                ($approvedLoanForRelease ? $approvedLoanForRelease->principal_amount : 0);
                        @endphp
                        <span class="hero-pill-approved">
                            <span>🎉</span>
                            <span>₱{{ number_format($totApproved, 2) }} Approved by Host (Ready to Claim)</span>
                        </span>
                    @endif
                    @if ($pendingUnderReviewWalletTx->count() > 0)
                        <span class="hero-pill-pending">
                            <span>⏳</span>
                            <span>₱{{ number_format($pendingUnderReviewWalletTx->sum('amount'), 2) }} Pending
                                Authorization</span>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Quick Action Buttons -->
        <div class="hero-action-buttons">
            <button type="button" class="btn btn-emerald hero-action-btn" onclick="openModal('cashInModal')">
                📥 Cash In
            </button>
            <button type="button" class="btn btn-amber hero-action-btn" onclick="openModal('cashOutModal')">
                📤 Cash Out
            </button>
            <button type="button" class="btn btn-primary hero-action-btn" style="background: #0284c7; border: none;"
                onclick="openModal('addSavingsModal')">
                🐖 Add Savings ({{ $savingsInterestRate }}%)
            </button>
            <button type="button" class="btn hero-action-btn"
                style="background: rgba(255,255,255,0.18); color: #ffffff; border: 1px solid rgba(255,255,255,0.35); backdrop-filter: blur(4px);"
                onclick="openModal('changePinModal')">
                🔐 Change PIN
            </button>
        </div>
    </div>

    <!-- 2. Paparating na Pondo na Na-approved ni Host (Ready for Release / Claiming) -->
    @if ($incomingApprovedWalletTx->count() > 0 || $approvedLoanForRelease)
        <div class="incoming-approved-banner">
            <div class="incoming-banner-top">
                <div>
                    <h3 class="incoming-banner-title">
                        <span>🎉</span>
                        <span>Paparating na Pondo — Na-approved na ni Host</span>
                    </h3>
                    <div style="font-size: 12.5px; color: #047857; margin-top: 2px;">
                        Inaprubahan na ng Superadmin/Host. Handa na ito para sa physical disbursement o crediting.
                    </div>
                </div>
                @php
                    $totalApprovedIncoming =
                        $incomingApprovedWalletTx->sum('amount') +
                        ($approvedLoanForRelease ? $approvedLoanForRelease->principal_amount : 0);
                @endphp
                <span class="badge badge-emerald" style="font-size: 13px; font-weight: 800; padding: 6px 14px;">
                    Kabuuang Paparating: ₱{{ number_format($totalApprovedIncoming, 2) }}
                </span>
            </div>

            <!-- Approved Loan Release Item -->
            @if ($approvedLoanForRelease)
                <div class="incoming-item-card" style="border-left: 4px solid #7c3aed;">
                    <div class="incoming-item-left">
                        <span class="incoming-tag loan">
                            📋 LOAN RELEASE APPROVED
                        </span>
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-top: 2px;">
                            Loan Application #{{ $approvedLoanForRelease->id }}
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Term: <strong>60 Days</strong> • Daily Installment:
                            <strong>₱{{ number_format($approvedLoanForRelease->daily_installment, 2) }}/day</strong>
                        </div>
                    </div>
                    <div>
                        <div class="incoming-item-amount" style="color: #7c3aed;">
                            ₱{{ number_format($approvedLoanForRelease->principal_amount, 2) }}
                        </div>
                        <div style="text-align: right; margin-top: 2px;">
                            <span class="badge badge-emerald" style="font-size: 10.5px;">Ready for Releasing</span>
                        </div>
                    </div>

                    <div class="incoming-timeline">
                        <span class="timeline-step done">✓ Encoded</span>
                        <span style="color: #94a3b8;">➔</span>
                        <span class="timeline-step done">✓ Host Approved</span>
                        <span style="color: #94a3b8;">➔</span>
                        <span class="timeline-step current">⚡ Ready for Releasing Officer Disbursement</span>
                    </div>
                </div>
            @endif

            <!-- Approved Wallet Transactions (Cash In / Cash Out) -->
            @foreach ($incomingApprovedWalletTx as $inTx)
                <div class="incoming-item-card"
                    style="border-left: 4px solid {{ $inTx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                    <div class="incoming-item-left">
                        <span class="incoming-tag {{ $inTx->type === 'cash_in' ? 'cashin' : 'cashout' }}">
                            {{ $inTx->type === 'cash_in' ? '📥 CASH IN APPROVED' : '📤 CASH OUT APPROVED' }}
                        </span>
                        <div style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-top: 2px;">
                            Transaction #{{ $inTx->id }} •
                            {{ $inTx->type === 'cash_in' ? 'Wallet Credit Pending Release' : 'Cash Withdrawal Payout' }}
                        </div>
                        <div
                            style="font-size: 12px; color: var(--text-secondary); display: flex; gap: 12px; flex-wrap: wrap; margin-top: 2px;">
                            <span>📅 Schedule: <strong>{{ $inTx->releasing_scheduled_date ?? 'Today' }}</strong></span>
                            @if ($inTx->host_notes)
                                <span>👑 Host Note: <strong>{{ $inTx->host_notes }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="incoming-item-amount"
                            style="color: {{ $inTx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                            ₱{{ number_format($inTx->amount, 2) }}
                        </div>
                        <div style="text-align: right; margin-top: 2px;">
                            <span class="badge badge-emerald" style="font-size: 10.5px;">Host Authorized</span>
                        </div>
                    </div>

                    <div class="incoming-timeline">
                        <span class="timeline-step done">✓ Request Sent</span>
                        <span style="color: #94a3b8;">➔</span>
                        <span class="timeline-step done">✓ Releasing Reviewed</span>
                        <span style="color: #94a3b8;">➔</span>
                        <span class="timeline-step done">✓ Host Approved</span>
                        <span style="color: #94a3b8;">➔</span>
                        <span class="timeline-step current">⚡ Ready for PIN Verification & Cash Handover</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 3. Mga Kasalukuyang Nakabinbin na Request (Under Review) -->
    @if ($pendingUnderReviewWalletTx->count() > 0)
        <div class="pending-requests-box">
            <div class="pending-box-header">
                <div
                    style="font-weight: 700; color: #92400e; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                    <span>⏳</span>
                    <span>Kasalukuyang Nakabinbin na Request (Under Review)</span>
                </div>
                <span class="badge badge-amber" style="font-weight: 700;">Total:
                    ₱{{ number_format($pendingUnderReviewWalletTx->sum('amount'), 2) }}</span>
            </div>

            @foreach ($pendingUnderReviewWalletTx as $pTx)
                <div class="pending-item-row">
                    <div>
                        <strong style="font-size: 12.5px; color: #78350f;">
                            {{ strtoupper(str_replace('_', ' ', $pTx->type)) }} #{{ $pTx->id }}
                        </strong>
                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                            Requested: {{ $pTx->created_at->format('M d, Y h:i A') }} •
                            @if ($pTx->status === 'pending_releasing_review')
                                <span style="color: #b45309; font-weight: 600;">Step 1: Releasing Officer Review</span>
                            @else
                                <span style="color: #4f46e5; font-weight: 600;">Step 2: Host Superadmin Authorization</span>
                            @endif
                        </div>
                    </div>
                    <div style="font-weight: 800; font-size: 15px; color: #92400e;">
                        ₱{{ number_format($pTx->amount, 2) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 4. Client QR Code Box -->
    <div class="client-qr-toggle-box">
        <div class="client-qr-header">
            <div>
                <h3
                    style="font-size: 14.5px; font-weight: 700; color: var(--brand-navy); margin: 0; display: flex; align-items: center; gap: 6px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        style="color: #0284c7;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                        </path>
                    </svg>
                    My Unique Client QR Code
                </h3>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                    Present this QR code to your collector during daily collections or transactions.
                </p>
            </div>
            <button type="button" class="btn btn-outline btn-sm" id="qr-toggle-btn" onclick="toggleClientQr()"
                style="font-weight: 600;">
                <span>Show QR Code</span>
            </button>
        </div>

        <div class="client-qr-image-display" id="client-qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($client->qr_code_token) }}"
                alt="My QR Code" style="width: 100%; max-width: 150px; height: auto; margin: 0 auto; display: block;">
            <div
                style="font-size: 11.5px; font-family: monospace; font-weight: 700; margin-top: 8px; color: var(--brand-navy); background: #f1f5f9; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                {{ $client->qr_code_token }}
            </div>
        </div>
    </div>

    <!-- 5. Active Loan Section -->
    @if ($activeLoan && $activeLoan->status === 'active')
        @php
            $insDaily = (float) ($activeLoan->insurance_premium_daily ?? ($loanInsurancePremium ?? 25.0));
            $totalTermDays = $loanSchedules->count() > 0 ? $loanSchedules->count() : 60;
            $progressPercent = min(100, round(($paidDaysCount / $totalTermDays) * 100));
            $extendedDaysCount = max(0, $totalTermDays - 60);
            $totalInsuranceForTerm = $insDaily * $totalTermDays;
            $totalCombinedPayable = $activeLoan->total_payable + $totalInsuranceForTerm;
            $loanDaily =
                (float) ($activeLoan->loan_premium_daily ??
                    ($activeLoan->daily_installment - $insDaily > 0
                        ? $activeLoan->daily_installment - $insDaily
                        : $activeLoan->daily_installment));
            $totalDaily = (float) ($activeLoan->total_daily_payable ?? $loanDaily + $insDaily);
            $totalCombinedPaid =
                (float) ($activeLoan->total_paid ?? $paymentHistory->where('status', 'paid')->sum('amount_paid'));
            $totalCombinedRemaining = max(0, $totalCombinedPayable - $totalCombinedPaid);
            $paidInsuranceSum = (float) $paymentHistory->where('status', 'paid')->sum('insurance_premium_amount');
            $remainingInsurance = max(0, $totalInsuranceForTerm - $paidInsuranceSum);
            $remainingLoanPrincipalInterest = max(0, $totalCombinedRemaining - $remainingInsurance);
        @endphp

        <!-- Delinquency Alerts if any -->
        @if ($missedPastDuesCount >= 3)
            <div
                style="background: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                <div style="font-size: 20px;">⚠️</div>
                <div style="flex: 1;">
                    <strong style="color: #9f1239; font-size: 13.5px;">Overdue Notice: {{ $missedPastDuesCount }} Missed
                        Daily Payments</strong>
                    <p style="margin: 2px 0 0 0; font-size: 12px; color: #be123c; line-height: 1.4;">
                        You have {{ $missedPastDuesCount }} missed daily installment(s) totaling
                        <strong>₱{{ number_format($missedPastDuesCount * $totalDaily, 2) }}</strong>.
                        Extension days (Day 61+) were automatically added to your repayment schedule. Please coordinate with
                        your collector.
                    </p>
                </div>
            </div>
        @elseif($missedPastDuesCount > 0)
            <div
                style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px;">
                <div style="font-size: 18px;">ℹ️</div>
                <div style="flex: 1; font-size: 12px; color: #92400e;">
                    <strong>Schedule Extended:</strong> {{ $extendedDaysCount }} extra day(s) added to give you time to
                    catch up on missed payments.
                </div>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📄 My Active Loan (#{{ $activeLoan->id }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        {{ $totalTermDays }}-Day Term Repayment Plan
                        @if ($extendedDaysCount > 0)
                            <span style="color: #d97706; font-weight: 600;">(Extended by {{ $extendedDaysCount }}
                                day{{ $extendedDaysCount > 1 ? 's' : '' }})</span>
                        @endif
                    </p>
                </div>
                <span class="badge badge-emerald">Active Loan</span>
            </div>

            <!-- Repayment Progress -->
            <div style="margin-bottom: 16px;">
                <div
                    style="display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; flex-wrap: wrap; gap: 4px;">
                    <span>Repayment Progress</span>
                    <span style="color: #059669;">
                        {{ $paidDaysCount }} / {{ $totalTermDays }} Days Completed ({{ $progressPercent }}%)
                        @if ($extendedDaysCount > 0)
                            <span class="badge badge-amber"
                                style="font-size: 10.5px; margin-left: 4px;">+{{ $extendedDaysCount }} Extended
                                Days</span>
                        @endif
                    </span>
                </div>
                <div class="loan-progress-container">
                    <div class="loan-progress-bar" style="width: {{ $progressPercent }}%;"></div>
                </div>
            </div>

            <!-- Loan KPI Cards -->
            <div class="loan-kpi-grid">
                <div class="loan-kpi-card">
                    <div class="loan-kpi-label">Principal Amount</div>
                    <div class="loan-kpi-val">₱{{ number_format($activeLoan->principal_amount, 2) }}</div>
                </div>
                <div class="loan-kpi-card">
                    <div class="loan-kpi-label">Total Payable</div>
                    <div class="loan-kpi-val" style="color: #047857;">
                        ₱{{ number_format($totalCombinedPayable, 2) }}
                        <span
                            style="font-size: 11px; font-weight: normal; color: var(--text-secondary); display: block; margin-top: 2px;">
                            ₱{{ number_format($activeLoan->total_payable, 2) }} Loan +
                            ₱{{ number_format($totalInsuranceForTerm, 2) }} Ins.
                        </span>
                    </div>
                </div>
                <div class="loan-kpi-card">
                    <div class="loan-kpi-label">Daily Amount Payable</div>
                    <div class="loan-kpi-val" style="color: #d97706;">
                        ₱{{ number_format($totalDaily, 2) }}
                        <span
                            style="font-size: 11px; font-weight: normal; color: var(--text-secondary); display: block; margin-top: 2px;">
                            ₱{{ number_format($loanDaily, 2) }} Loan + ₱{{ number_format($insDaily, 2) }} Ins.
                        </span>
                    </div>
                </div>
                <div class="loan-kpi-card">
                    <div class="loan-kpi-label">Remaining Balance</div>
                    <div class="loan-kpi-val" style="color: #059669;">
                        ₱{{ number_format($totalCombinedRemaining, 2) }}
                        <span
                            style="font-size: 11px; font-weight: normal; color: var(--text-secondary); display: block; margin-top: 2px;">
                            ₱{{ number_format($remainingLoanPrincipalInterest, 2) }} Loan +
                            ₱{{ number_format($remainingInsurance, 2) }} Ins.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Insurance Premium Breakdown Box -->
            <div
                style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <div
                            style="font-size: 13px; font-weight: 700; color: #1e40af; display: flex; align-items: center; gap: 6px;">
                            <span>🛡️</span> Daily Payable Breakdown & Micro-Insurance Protection
                        </div>
                        <div
                            style="font-size: 12.5px; color: #334155; margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>Loan Premium: <strong>₱{{ number_format($loanDaily, 2) }}</strong></span>
                            <span style="color: #64748b; font-weight: bold;">+</span>
                            <span>Insurance Premium: <strong
                                    style="color: #0284c7;">₱{{ number_format($insDaily, 2) }}</strong></span>
                            <span style="color: #64748b; font-weight: bold;">=</span>
                            <span style="color: #059669; font-weight: 800;">Daily Total:
                                ₱{{ number_format($totalDaily, 2) }}</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <a href="{{ route('client.insurance') }}" class="btn btn-sm btn-outline"
                            style="background: #ffffff; border-color: #0284c7; color: #0284c7; font-weight: 700; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;">
                            <span>🛡️ View Policy Details</span> &rarr;
                        </a>
                        <div
                            style="font-size: 11.5px; background: #ffffff; padding: 5px 12px; border-radius: 6px; border: 1px solid #cbd5e1; color: #475569; font-weight: 600;">
                            🕛 12:00 MN Auto-Wallet Deduction Active
                        </div>
                    </div>
                </div>
            </div>

            @php
                $pendingProcessingPayments = $paymentHistory->where('status', 'processing');
            @endphp
            @if ($pendingProcessingPayments->count() > 0)
                <div
                    style="background: #fefce8; border: 1px solid #fde047; border-left: 4px solid #eab308; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">⏳</span>
                    <div style="font-size: 12.5px; color: #854d0e;">
                        <strong>Processing Collection Payment:</strong> Your collector collected
                        <strong>₱{{ number_format($pendingProcessingPayments->sum('amount_paid'), 2) }}</strong> today.
                        Status will automatically update to <span class="badge badge-emerald" style="font-size: 10px;">✓
                            Paid</span> as soon as Admin Finance receives and verifies the remittance in the office.
                    </div>
                </div>
            @endif

            <!-- Payment Schedule Table -->
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <h4 style="font-size: 14px; font-weight: 700; margin: 0; color: var(--text-primary);">
                    📅 {{ $totalTermDays }}-Day Payment Schedule & Breakdown
                </h4>
                <span style="font-size: 12px; color: var(--text-secondary);">
                    Includes <strong>Loan Premium</strong> + <strong>Insurance Premium
                        (₱{{ number_format($insDaily, 2) }}/day)</strong>
                </span>
            </div>
            <div class="table-responsive"
                style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table class="data-table">
                    <thead style="position: sticky; top: 0; background: var(--bg-card); z-index: 1;">
                        <tr>
                            <th>Day #</th>
                            <th>Due Date</th>
                            <th>Loan Premium</th>
                            <th>Insurance Premium</th>
                            <th>Total Payable</th>
                            <th>Amount Paid</th>
                            <th>Status</th>
                            <th>Paid Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loanSchedules as $schedule)
                            @php
                                $isPastDue =
                                    \Carbon\Carbon::parse($schedule->due_date)->isPast() &&
                                    !\Carbon\Carbon::parse($schedule->due_date)->isToday();
                                $isToday = \Carbon\Carbon::parse($schedule->due_date)->isToday();
                                $isExtended = $schedule->day_number > 60;
                                $dayLoanPremium =
                                    (float) ($schedule->expected_amount > 0 ? $schedule->expected_amount : $loanDaily);
                                $dayTotalPayable = $dayLoanPremium + $insDaily;

                                $isFullySettled =
                                    $totalCombinedRemaining <= 0 ||
                                    $activeLoan->remaining_balance <= 0 ||
                                    $activeLoan->status === 'fully_paid';
                                $isSchedulePaid =
                                    $schedule->status === 'paid' ||
                                    ($isFullySettled &&
                                        ($schedule->paid_amount > 0 ||
                                            $schedule->day_number == $activeLoan->term_days));

                                $dayPaidAmount = 0;
                                if ($isSchedulePaid) {
                                    $dayPaidAmount = $dayTotalPayable;
                                } elseif ($schedule->paid_amount > 0) {
                                    $dayPaidAmount = $schedule->paid_amount + $insDaily;
                                }
                            @endphp
                            <tr @if ($isExtended) style="background: rgba(245, 158, 11, 0.04);" @endif>
                                <td>
                                    <strong>Day {{ $schedule->day_number }}</strong>
                                    @if ($isExtended)
                                        <span class="badge badge-amber"
                                            style="font-size: 9.5px; padding: 2px 5px; margin-left: 2px;">Extra</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $schedule->due_date }}
                                    @if ($isToday)
                                        <span class="badge badge-primary"
                                            style="font-size: 9.5px; padding: 1px 4px; margin-left: 2px;">Today</span>
                                    @endif
                                </td>
                                <td>₱{{ number_format($dayLoanPremium, 2) }}</td>
                                <td style="color: #0284c7; font-weight: 600;">₱{{ number_format($insDaily, 2) }}</td>
                                <td style="font-weight: 700; color: #166534;">₱{{ number_format($dayTotalPayable, 2) }}
                                </td>
                                <td style="font-weight: 700; color: {{ $dayPaidAmount > 0 ? '#059669' : 'inherit' }};">
                                    @if ($dayPaidAmount > 0)
                                        <div>₱{{ number_format($dayPaidAmount, 2) }}</div>
                                        <div style="font-size: 9.5px; color: var(--text-muted); font-weight: normal;">
                                            (₱{{ number_format($dayLoanPremium, 2) }} +
                                            ₱{{ number_format($insDaily, 2) }})</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($isSchedulePaid)
                                        <span class="badge badge-emerald">✓ Paid</span>
                                    @elseif($schedule->status === 'partial')
                                        <span class="badge badge-amber">Partial
                                            (₱{{ number_format($dayPaidAmount, 2) }})
                                        </span>
                                    @elseif($isPastDue)
                                        <span class="badge badge-rose">⚠️ Missed</span>
                                    @else
                                        <span class="badge badge-slate">Pending</span>
                                    @endif
                                </td>
                                <td style="font-size: 11.5px; color: var(--text-secondary);">
                                    {{ $schedule->paid_at ? \Carbon\Carbon::parse($schedule->paid_at)->format('M d, Y h:i A') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(
        $activeLoan &&
            in_array($activeLoan->status, [
                'pending_host_approval',
                'approved_for_release',
                'ready_for_release',
                'pending_releasing_review',
            ]))
        <div class="card" style="border-left: 4px solid #f59e0b;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">⏳ Loan Application Under Review</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Principal: <strong>₱{{ number_format($activeLoan->principal_amount, 2) }}</strong>
                    </p>
                </div>
                <span class="badge badge-amber">Pending Release</span>
            </div>
            <div style="padding: 16px; font-size: 13px; color: var(--text-secondary);">
                Your loan request for <strong
                    style="color: #059669;">₱{{ number_format($activeLoan->principal_amount, 2) }}</strong> is undergoing
                authorization and will be disbursed shortly upon PIN verification.
            </div>
        </div>
    @else
        <div class="card" style="border-left: 4px solid #059669; background: #f0fdf4;">
            <div
                style="padding: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #065f46; margin: 0 0 3px 0;">🎉 You are eligible
                        for a Loan Renewal (Re-Loan)!</h3>
                    <p style="margin: 0; font-size: 12px; color: #047857;">You currently have no active loan. Apply for a
                        new loan cycle anytime.</p>
                </div>
                <button type="button" class="btn btn-emerald" onclick="openModal('applyRenewalModal')"
                    style="font-weight: 700;">
                    🔄 Apply for Loan Renewal
                </button>
            </div>
        </div>
    @endif

    <!-- 6. Savings Funds Section -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">🐖 My Active Savings Funds</h3>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                    Fixed growth savings with daily interest automatically credited to your wallet, and full capital + final
                    interest released on maturity.
                </p>
            </div>
            <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addSavingsModal')">
                + Open New Savings Fund ({{ $savingsInterestRate }}%)
            </button>
        </div>

        @if ($savingsAccounts->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary); font-size: 12.5px;">
                You currently have no active savings fund. Open one today to earn {{ $savingsInterestRate }}% interest
                locked for {{ $savingsLockInDays }} days!
            </div>
        @else
            <div class="host-approval-grid">
                @foreach ($savingsAccounts as $sav)
                    @php
                        $remainingInterest = max(
                            0,
                            (float) $sav->total_expected_interest - (float) $sav->accumulated_interest_paid,
                        );
                        $finalPayout = (float) $sav->deposit_amount + $remainingInterest;
                        $progressPercent =
                            $sav->lock_in_days > 0
                                ? min(100, round(($sav->days_credited / $sav->lock_in_days) * 100))
                                : 0;
                    @endphp
                    <div class="approval-card" style="border-left-color: #059669;">
                        <div>
                            <div class="approval-header">
                                <h4 style="font-size: 14.5px;">Deposit: ₱{{ number_format($sav->deposit_amount, 2) }}</h4>
                                <span class="badge badge-emerald">{{ number_format($sav->interest_rate_percent, 1) }}% /
                                    {{ $sav->lock_in_days }} Days</span>
                            </div>

                            <!-- Progress Bar -->
                            <div style="margin: 10px 0 12px 0;">
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 11.5px; margin-bottom: 4px;">
                                    <span style="font-weight: 600; color: var(--text-secondary);">Daily Term
                                        Progress:</span>
                                    <strong style="color: #059669;">Day {{ $sav->days_credited }} of
                                        {{ $sav->lock_in_days }} ({{ $progressPercent }}%)</strong>
                                </div>
                                <div
                                    style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                    <div
                                        style="width: {{ $progressPercent }}%; height: 100%; background: linear-gradient(90deg, #10b981, #059669); border-radius: 999px; transition: width 0.3s ease;">
                                    </div>
                                </div>
                            </div>

                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Daily Interest (Credited Daily)</span>
                                <span class="approval-meta-val"
                                    style="color: #059669; font-weight: 700;">+₱{{ number_format($sav->daily_interest_amount, 2) }}
                                    / day</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Total Interest Received so far</span>
                                <span class="approval-meta-val"
                                    style="font-weight: 600;">₱{{ number_format($sav->accumulated_interest_paid, 2) }}
                                    <span style="font-weight: normal; color: var(--text-secondary);">/
                                        ₱{{ number_format($sav->total_expected_interest, 2) }}</span></span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Maturity Date</span>
                                <span class="approval-meta-val">{{ $sav->maturity_date }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Status</span>
                                <span class="approval-meta-val">
                                    @if ($sav->status === 'matured')
                                        <span class="badge badge-emerald"
                                            style="background: #059669; color: #fff; font-weight: 700;">✓ Matured &
                                            Paid</span>
                                    @else
                                        <span class="badge badge-emerald">{{ ucfirst($sav->status) }}</span>
                                    @endif
                                </span>
                            </div>

                            @if ($sav->status === 'active')
                                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 14px;">
                                    <!-- Test/Simulate 1-Day Daily Interest -->
                                    <form action="{{ route('client.savings.simulate_day', $sav->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline"
                                            style="width: 100%; font-size: 11.5px; border-color: #059669; color: #047857; display: flex; align-items: center; justify-content: center; gap: 4px;"
                                            title="Credit 1 day of daily interest to wallet balance">
                                            <span>⚡ Test: Credit +1 Day Interest
                                                (+₱{{ number_format($sav->daily_interest_amount, 2) }})</span>
                                        </button>
                                    </form>

                                    <!-- Fast-forward Maturity -->
                                    <form action="{{ route('client.savings.mature', $sav->id) }}" method="POST"
                                        onsubmit="return confirm('⚡ DEMO / FAST-FORWARD: Settle Maturity now?\n\nCapital Deposit: ₱{{ number_format($sav->deposit_amount, 2) }}\nRemaining Uncredited Interest: ₱{{ number_format($remainingInterest, 2) }}\nTOTAL MATURITY PAYOUT: ₱{{ number_format($finalPayout, 2) }}\n\nThis will credit ₱{{ number_format($finalPayout, 2) }} directly to your Available Wallet Balance!')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-emerald"
                                            style="width: 100%; font-weight: 700; background: linear-gradient(135deg, #059669 0%, #0d9488 100%); display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 2px 6px rgba(5,150,105,0.25);">
                                            <span>⚡ Settle Maturity (Capital ₱{{ number_format($sav->deposit_amount, 2) }}
                                                + ₱{{ number_format($remainingInterest, 2) }} Interest)</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div
                                    style="margin-top: 10px; background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.25); border-radius: 6px; padding: 8px 10px; font-size: 11.5px; color: #047857; font-weight: 600; text-align: center;">
                                    ✓ Matured & Full Capital (+₱{{ number_format($sav->deposit_amount, 2) }}) and Total
                                    Interest (+₱{{ number_format($sav->total_expected_interest, 2) }}) Successfully
                                    Credited
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 7. Wallet Transaction History Table -->
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
                    @if (!$walletTransactions->isEmpty())
                        @foreach ($walletTransactions as $wTx)
                            @php
                                $isPositive = in_array($wTx->type, ['cash_in', 'savings_payout', 'daily_interest']);
                            @endphp
                            <tr>
                                <td>{{ $wTx->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <span class="badge {{ $isPositive ? 'badge-emerald' : 'badge-amber' }}">
                                        {{ strtoupper(str_replace('_', ' ', $wTx->type)) }}
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: {{ $isPositive ? '#059669' : '#d97706' }};">
                                    {{ $isPositive ? '+' : '-' }}₱{{ number_format($wTx->amount, 2) }}
                                </td>
                                <td>
                                    @if ($wTx->status === 'completed')
                                        <span class="badge badge-emerald">✓ Completed</span>
                                    @elseif($wTx->status === 'approved_by_host')
                                        <span class="badge badge-emerald"
                                            style="background: #059669; color: #fff; font-weight: 700;">✓ Approved
                                            (Ready)
                                        </span>
                                    @elseif($wTx->status === 'pending_host_approval')
                                        <span class="badge badge-indigo">⏳ For Host Approval</span>
                                    @elseif($wTx->status === 'pending_releasing_review')
                                        <span class="badge badge-amber">🔍 Under Review</span>
                                    @elseif($wTx->status === 'declined')
                                        <span class="badge badge-rose">✕ Declined</span>
                                    @else
                                        <span class="badge badge-slate">{{ str_replace('_', ' ', $wTx->status) }}</span>
                                    @endif
                                </td>
                                <td style="font-size: 12px; color: var(--text-secondary);">
                                    {{ $wTx->releasing_notes ?? ($wTx->decline_reason ?? '-') }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 20px; color: var(--text-muted);">No
                                wallet transactions yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= MODALS ================= -->

    <!-- Cash In Request Modal -->
    <div class="modal-overlay" id="cashInModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">📥 Request Cash In to Wallet</h3>
                <button type="button" class="modal-close" onclick="closeModal('cashInModal')">&times;</button>
            </div>
            <form action="{{ route('client.cash_in') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                        Enter the amount you wish to Cash In. A releasing officer or collector will collect the cash and
                        credit your balance.
                    </p>
                    <div class="form-group">
                        <label class="form-label">Cash In Amount (₱) *</label>
                        <input type="number" step="0.01" min="100" name="amount"
                            class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}"
                            placeholder="e.g. 3000" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2"
                            placeholder="Preferred collection time or instructions...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                <h3 class="modal-title">📤 Request Cash Out / Withdrawal</h3>
                <button type="button" class="modal-close" onclick="closeModal('cashOutModal')">&times;</button>
            </div>
            <form action="{{ route('client.cash_out') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div
                        style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); margin-bottom: 14px;">
                        <div style="font-size: 11.5px; color: var(--text-secondary);">Current Available Balance:</div>
                        <div style="font-size: 20px; font-weight: 700; color: #059669;">
                            ₱{{ number_format($client->wallet_balance, 2) }}</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cash Out Amount (₱) *</label>
                        <input type="number" step="0.01" min="100" max="{{ $client->wallet_balance }}"
                            name="amount" class="form-control @error('amount') is-invalid @enderror"
                            value="{{ old('amount') }}" placeholder="e.g. 1000" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2"
                            placeholder="Preferred release schedule...">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                <h3 class="modal-title">🐖 Open {{ $savingsLockInDays }}-Day {{ $savingsInterestRate }}% Savings Fund
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('addSavingsModal')">&times;</button>
            </div>
            <form action="{{ route('client.savings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="savings-plan-card" style="margin-bottom: 14px;">
                        <span class="savings-rate-tag">{{ $savingsInterestRate }}% GUARANTEED RETURN</span>
                        <h4 style="font-size: 14.5px; margin-bottom: 4px; font-weight: 700;">{{ $savingsLockInDays }}-Day
                            Fixed Lock-in Growth</h4>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">
                            Daily interest is automatically credited directly to your wallet every day (Days
                            1–{{ $savingsLockInDays - 1 }}). On Day {{ $savingsLockInDays }} (Maturity), your full
                            Capital Deposit + final 1-day interest will be released!
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Savings Deposit Amount (₱) *</label>
                        <input type="number" step="0.01" min="500" max="{{ $client->wallet_balance }}"
                            name="deposit_amount" id="savings_deposit_amount" data-rate="{{ $savingsInterestRate }}"
                            data-days="{{ $savingsLockInDays }}"
                            class="form-control @error('deposit_amount') is-invalid @enderror"
                            value="{{ old('deposit_amount') }}" placeholder="e.g. 10000" required
                            oninput="previewSavingsCalculation()">
                        <div class="form-hint">Deducted from your current wallet balance
                            (₱{{ number_format($client->wallet_balance, 2) }}).</div>
                        @error('deposit_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Live Calculation Preview -->
                    <div
                        style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); font-size: 12.5px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-secondary);">Daily Interest (Credited Every Day):</span>
                            <strong style="color: #059669;" id="preview_daily_interest">₱0.00 / day</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="color: var(--text-secondary);">Total {{ $savingsLockInDays }}-Day
                                Interest:</span>
                            <strong id="preview_total_interest">₱0.00</strong>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; border-top: 1px dashed var(--border-color); padding-top: 5px;">
                            <span style="font-weight: 600;">Maturity Day Release (Capital + Final Interest):</span>
                            <strong style="color: #059669; font-size: 14px;" id="preview_total_maturity">₱0.00</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline"
                        onclick="closeModal('addSavingsModal')">Cancel</button>
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
                        <input type="password" name="current_pin"
                            class="form-control @error('current_pin') is-invalid @enderror" maxlength="4"
                            pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required
                            autocomplete="current-password">
                        @error('current_pin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">New 4-Digit PIN *</label>
                        <input type="password" name="new_pin" class="form-control @error('new_pin') is-invalid @enderror"
                            maxlength="4" pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required
                            autocomplete="new-password">
                        <div class="form-hint">Must be exactly 4 numerical digits.</div>
                        @error('new_pin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New PIN *</label>
                        <input type="password" name="new_pin_confirmation"
                            class="form-control @error('new_pin_confirmation') is-invalid @enderror" maxlength="4"
                            pattern="[0-9]{4}" inputmode="numeric" placeholder="••••" required
                            autocomplete="new-password">
                        @error('new_pin_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
        <div class="modal-box" style="max-width: 520px;">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">🔄 Apply for Loan Renewal (Re-Loan)</h3>
                    <div style="font-size: 12px; color: var(--text-secondary);">Request a new loan cycle under verified
                        terms.</div>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('applyRenewalModal')">&times;</button>
            </div>
            <form action="{{ route('client.request_renewal') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div
                        style="background: rgba(5, 150, 105, 0.08); border: 1px solid rgba(5, 150, 105, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: #047857; margin-bottom: 4px;">📌 Verified
                            System Terms:</div>
                        <div
                            style="display: flex; gap: 16px; font-size: 12.5px; color: var(--text-primary); flex-wrap: wrap;">
                            <div><strong>Interest:</strong> {{ $loanInterestRate ?? 10 }}%</div>
                            <div><strong>Term:</strong> {{ $loanTermDays ?? 60 }} Days (Daily)</div>
                            <div><strong>Insurance:</strong> <span
                                    style="color: #0284c7; font-weight: 700;">₱{{ number_format($loanInsurancePremium ?? 25, 2) }}
                                    / day</span></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Requested Loan Amount (₱) <span style="color:red;">*</span></label>
                        <input type="number" step="100" min="500" name="amount" id="client_renew_principal"
                            class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}"
                            placeholder="Enter amount, e.g. 10000" required oninput="calculateClientRenewLoan()">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        <!-- Quick Presets -->
                        <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-sm btn-outline"
                                style="padding: 2px 8px; font-size: 11px;"
                                onclick="setClientRenewAmount(5000)">₱5,000</button>
                            <button type="button" class="btn btn-sm btn-outline"
                                style="padding: 2px 8px; font-size: 11px;"
                                onclick="setClientRenewAmount(10000)">₱10,000</button>
                            <button type="button" class="btn btn-sm btn-outline"
                                style="padding: 2px 8px; font-size: 11px;"
                                onclick="setClientRenewAmount(15000)">₱15,000</button>
                            <button type="button" class="btn btn-sm btn-outline"
                                style="padding: 2px 8px; font-size: 11px;"
                                onclick="setClientRenewAmount(20000)">₱20,000</button>
                            <button type="button" class="btn btn-sm btn-outline"
                                style="padding: 2px 8px; font-size: 11px;"
                                onclick="setClientRenewAmount(30000)">₱30,000</button>
                        </div>
                    </div>

                    <!-- Live Breakdown Preview -->
                    <div
                        style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <div
                            style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">
                            Estimated Loan & Insurance Computation</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Loan
                                    Interest ({{ $loanInterestRate ?? 10 }}%)</span>
                                <span style="font-size: 14px; font-weight: 700; color: #d97706;"
                                    id="client_renew_interest">₱0.00</span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Loan
                                    Principal+Int.</span>
                                <span style="font-size: 14px; font-weight: 800; color: #059669;"
                                    id="client_renew_payable">₱0.00</span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Daily Loan
                                    Amortization</span>
                                <span style="font-size: 13.5px; font-weight: 700; color: #334155;"
                                    id="client_renew_loan_daily">₱0.00 / day</span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Daily
                                    Insurance Premium</span>
                                <span style="font-size: 13.5px; font-weight: 700; color: #0284c7;"
                                    id="client_renew_ins_daily">₱{{ number_format($loanInsurancePremium ?? 25, 2) }} /
                                    day</span>
                            </div>
                            <div
                                style="grid-column: span 2; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                <div>
                                    <span style="font-size: 12px; color: var(--text-secondary); display: block;">Total
                                        Daily Due:</span>
                                    <strong style="font-size: 17px; color: #15803d;" id="client_renew_daily">₱0.00 /
                                        day</strong>
                                </div>
                                <div style="text-align: right;">
                                    <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total
                                        Combined Payable ({{ $loanTermDays ?? 60 }} Days):</span>
                                    <strong style="font-size: 14px; color: #0f172a;"
                                        id="client_renew_total_combined">₱0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2"
                            placeholder="e.g. Additional capital for business expansion">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline"
                        onclick="closeModal('applyRenewalModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">Submit Renewal Request
                        &rarr;</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/client.js') }}"></script>
    <script>
        const clientLoanRate = {{ (float) ($loanInterestRate ?? 10) }};
        const clientLoanTerm = {{ (int) ($loanTermDays ?? 60) }};
        const clientInsuranceDaily = {{ (float) ($loanInsurancePremium ?? 25.0) }};

        function setClientRenewAmount(val) {
            document.getElementById('client_renew_principal').value = val;
            calculateClientRenewLoan();
        }

        function calculateClientRenewLoan() {
            const principal = parseFloat(document.getElementById('client_renew_principal').value) || 0;
            const interest = principal * (clientLoanRate / 100);
            const loanTotal = principal + interest;
            const loanDaily = clientLoanTerm > 0 ? (loanTotal / clientLoanTerm) : 0;
            const totalDaily = loanDaily > 0 ? (loanDaily + clientInsuranceDaily) : 0;
            const totalInsurance = clientInsuranceDaily * clientLoanTerm;
            const totalCombined = loanTotal > 0 ? (loanTotal + totalInsurance) : 0;

            document.getElementById('client_renew_interest').innerText = '₱' + interest.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('client_renew_payable').innerText = '₱' + loanTotal.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('client_renew_loan_daily').innerText = '₱' + loanDaily.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' / day';
            document.getElementById('client_renew_daily').innerText = '₱' + totalDaily.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' / day';
            document.getElementById('client_renew_total_combined').innerText = '₱' + totalCombined.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    </script>
@endpush
