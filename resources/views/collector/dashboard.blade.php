@extends('layouts.app')

@section('title', 'Collector Portal')
@section('page_title', 'Collector - Field Portal & Collections Management')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/collector.css') }}">
    <style>
        .collector-tabs-nav {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .collector-tab-btn {
            background: none;
            border: none;
            padding: 10px 18px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-secondary);
            border-radius: var(--radius-md) var(--radius-md) 0 0;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-fast);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .collector-tab-btn:hover {
            color: #059669;
            background: #f0fdf4;
        }
        .collector-tab-btn.active {
            color: #059669;
            border-bottom-color: #059669;
            background: #ffffff;
            font-weight: 700;
        }
        .collector-tab-pane {
            display: none;
        }
        .collector-tab-pane.active {
            display: block;
        }
        .remit-hero-banner {
            background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 18px 22px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-md);
        }
        .remit-stat-box {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            padding: 10px 16px;
            min-width: 140px;
        }
    </style>
@endpush

@section('content')
    <!-- Top Summary & Commission Hero Card -->
    <div class="collector-hero-balance" style="margin-bottom: 16px;">
        <div>
            <div class="collector-balance-title">My Total Accumulated Commission Balance</div>
            <div class="collector-balance-val">₱{{ number_format($collector->commission_balance, 2) }}</div>
            <div class="collector-commission-rates">
                <span class="commission-badge">₱300 per Fully-Paid Loan</span>
                <span class="commission-badge">5% Automated on Client Savings</span>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('collector.scan_qr') }}" class="btn btn-emerald" style="background: #ffffff; color: #065f46; font-weight: 700; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                📷 Scan Client QR / Collect Payment
            </a>
            <button type="button" class="btn btn-outline" style="color: #ffffff; border-color: rgba(255,255,255,0.4); font-weight: 600;" onclick="openModal('collectorCashoutModal')">
                💰 Encash Commission
            </button>
        </div>
    </div>

    <!-- Delinquency Warning Alert (3 Consecutive Missed Days) -->
    @if($delinquentClients->isNotEmpty())
        <div class="delinquent-alert-banner" style="margin-bottom: 16px;">
            <span style="font-size: 22px;">⚠</span>
            <div>
                <strong>Delinquency Warning (3+ Consecutive Days Without Payment)</strong>
                <p style="margin: 2px 0 0 0; font-size: 12px;">
                    The following client accounts are flagged for non-payment:
                    @foreach($delinquentClients as $dc)
                        <span style="font-weight: 700; text-decoration: underline;">{{ $dc->user->name }} ({{ $dc->consecutive_missed_days }} days missed)</span>{{ !$loop->last ? ',' : '' }}
                    @endforeach
                    . Uncollected balances may be charged to collector liability if unresolved.
                </p>
            </div>
        </div>
    @endif

    <!-- Navigation Switcher Tabs -->
    <div class="collector-tabs-nav">
        <button type="button" class="collector-tab-btn active" id="btn-col-tab-today" onclick="switchCollectorTab('col-tab-today')">
            <span>📥 Today's Field Collections</span>
            @if($pendingRemittanceCount > 0)
                <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 800; font-size: 11px; padding: 2px 7px; border-radius: 9999px;">
                    {{ $pendingRemittanceCount }} Pending Remit
                </span>
            @else
                <span class="badge badge-slate" style="font-size: 11px;">{{ $todayCollections->count() }}</span>
            @endif
        </button>
        <button type="button" class="collector-tab-btn" id="btn-col-tab-clients" onclick="switchCollectorTab('col-tab-clients')">
            <span>👥 Assigned Clients</span>
            <span class="badge badge-slate" style="font-size: 11px;">{{ $assignedClients->count() }}</span>
        </button>
        <button type="button" class="collector-tab-btn" id="btn-col-tab-history" onclick="switchCollectorTab('col-tab-history')">
            <span>📜 Recent Collection Records</span>
        </button>
    </div>

    <!-- TAB 1: TODAY'S FIELD COLLECTIONS & REMITTANCE -->
    <div class="collector-tab-pane active" id="col-tab-today">
        <!-- Remittance Overview Banner -->
        <div class="remit-hero-banner">
            <div>
                <span style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9;">Today's Field Collection Summary</span>
                <h2 style="font-size: 26px; margin: 4px 0 0 0; font-weight: 800; color: #ffffff;">
                    ₱{{ number_format($todayTotalCollected, 2) }}
                </h2>
                <div style="font-size: 12px; opacity: 0.85; margin-top: 4px;">
                    {{ $todayCollections->count() }} client payments collected today
                </div>
            </div>

            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <div class="remit-stat-box">
                    <div style="font-size: 11px; opacity: 0.85;">Pending Office Remit</div>
                    <div style="font-size: 18px; font-weight: 800; color: #fef08a;">₱{{ number_format($todayProcessingAmount, 2) }}</div>
                    <div style="font-size: 10.5px; opacity: 0.8;">{{ $pendingRemittanceCount }} processing</div>
                </div>

                <div class="remit-stat-box">
                    <div style="font-size: 11px; opacity: 0.85;">Remitted & Verified (PAID)</div>
                    <div style="font-size: 18px; font-weight: 800; color: #a7f3d0;">₱{{ number_format($todayRemittedAmount, 2) }}</div>
                    <div style="font-size: 10.5px; opacity: 0.8;">{{ $todayCollections->where('status', 'paid')->count() }} verified</div>
                </div>

                @if($pendingRemittanceCount > 0)
                    <div>
                        <button type="button" class="btn btn-primary" style="background: #ffffff; color: #047857; font-weight: 800; border: none; padding: 12px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);" onclick="openModal('remitCollectionModal')">
                            💼 Remit to Office (Admin Finance)
                        </button>
                    </div>
                @else
                    <div>
                        <button type="button" class="btn btn-primary" style="background: rgba(255,255,255,0.2); color: #ffffff; cursor: not-allowed; border: 1px solid rgba(255,255,255,0.3);" disabled>
                            ✓ All Remitted
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Today's Collections Table -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📥 Payments Collected Today ({{ $todayCollections->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Payments remain in <strong>Processing</strong> status until remitted at the office with Admin Finance PIN verification.
                    </p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Client Name</th>
                            <th>Total Amount Paid</th>
                            <th>Loan Premium</th>
                            <th>Insurance Premium</th>
                            <th>Status</th>
                            <th>Proof Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$todayCollections->isEmpty())
                            @foreach($todayCollections as $tc)
                                <tr>
                                    <td style="font-size: 12px; font-weight: 600; color: var(--text-secondary);">
                                        {{ $tc->created_at->format('h:i A') }}
                                    </td>
                                    <td>
                                        <strong>{{ $tc->client->user->name ?? 'N/A' }}</strong>
                                        <div style="font-size: 11px; color: var(--text-muted);">📞 {{ $tc->client->user->phone_number ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 800; color: #059669; font-size: 14.5px;">
                                            ₱{{ number_format($tc->amount_paid, 2) }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; font-size: 12.5px;">
                                        ₱{{ number_format($tc->loan_premium_amount > 0 ? $tc->loan_premium_amount : $tc->amount_paid, 2) }}
                                    </td>
                                    <td style="font-weight: 600; font-size: 12.5px; color: #0284c7;">
                                        ₱{{ number_format($tc->insurance_premium_amount, 2) }}
                                    </td>
                                    <td>
                                        @if($tc->status === 'processing')
                                            <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-weight: 700; font-size: 11px; padding: 4px 8px;">
                                                ⏳ Processing (Pending Office Remit)
                                            </span>
                                        @else
                                            <span class="badge badge-emerald" style="font-weight: 700; font-size: 11px; padding: 4px 8px;">
                                                ✓ Remitted & Verified (PAID)
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tc->proof_image_path)
                                            <a href="{{ asset($tc->proof_image_path) }}" target="_blank" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 11.5px;">
                                                🔍 Proof
                                            </a>
                                        @else
                                            <span style="font-size: 11px; color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 30px; color: var(--text-muted);">
                                    No field collections logged today yet. Use <strong>Scan Client QR</strong> to collect payments.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: MY ASSIGNED CLIENTS -->
    <div class="collector-tab-pane" id="col-tab-clients">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👥 My Assigned Clients ({{ $assignedClients->count() }})</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Contact No (CP)</th>
                            <th>Active Loan</th>
                            <th>Daily Due (Loan + Ins)</th>
                            <th>Remaining Balance</th>
                            <th>Days Paid</th>
                            <th>Last Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$assignedClients->isEmpty())
                            @foreach($assignedClients as $c)
                                @php 
                                    $loan = $c->currentLoan; 
                                    $isActive = $loan && $loan->status === 'active';
                                    $isPendingLoan = ($c->status === 'pending_host_approval' || $c->status === 'pending') || ($loan && in_array($loan->status, ['pending_host_approval', 'pending_approval', 'pending']));
                                    $isApprovedForRelease = $loan && in_array($loan->status, ['approved_for_release', 'ready_for_release', 'releasing_in_process']);
                                    $isCompleted = $loan && $loan->status === 'completed';
                                    
                                    // Check if user has a pending wallet transaction (Cash-In, Cash-Out)
                                    $pendingWalletTx = $c->user && $c->user->walletTransactions ? $c->user->walletTransactions->whereIn('status', ['pending_releasing_review', 'pending_host_approval', 'approved_by_host'])->first() : null;
                                    
                                    $insDaily = (float)($loan && $loan->insurance_premium_daily > 0 ? $loan->insurance_premium_daily : 5.00);
                                    $loanDaily = (float)($loan ? ($loan->loan_premium_daily > 0 ? $loan->loan_premium_daily : $loan->daily_installment) : 0);
                                    $totDaily = $loanDaily + $insDaily;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $c->user->name }}</strong>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">{{ $c->user->address }}</div>
                                        @if($pendingWalletTx)
                                            <div style="margin-top: 3px;">
                                                <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 10px; font-weight: 700;">
                                                    ⏳ Pending {{ strtoupper(str_replace('_', ' ', $pendingWalletTx->type)) }} (₱{{ number_format($pendingWalletTx->amount, 2) }})
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $c->user->phone_number }}</td>
                                    <td>
                                        @if($isActive)
                                            <span class="badge badge-emerald">₱{{ number_format($loan->principal_amount, 2) }}</span>
                                        @elseif($isApprovedForRelease)
                                            <span class="badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 10.5px; font-weight: 700;">
                                                📦 Ready for Release
                                            </span>
                                        @elseif($isPendingLoan)
                                            <span class="badge badge-amber" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">
                                                ⏳ Pending Host Approval
                                            </span>
                                        @else
                                            <span class="badge badge-slate">No Active Loan</span>
                                        @endif
                                    </td>
                                    <td style="font-weight: 700; color: #d97706;">
                                        @if($isActive)
                                            <div>₱{{ number_format($totDaily, 2) }}</div>
                                            <div style="font-size: 10.5px; color: var(--text-muted); font-weight: normal;">(₱{{ number_format($loanDaily, 2) }} + ₱{{ number_format($insDaily, 2) }})</div>
                                        @else
                                            <span style="color: var(--text-muted); font-weight: normal;">-</span>
                                        @endif
                                    </td>
                                    <td style="font-weight: 700; color: #059669;">
                                        @if($isActive)
                                            ₱{{ number_format($loan->remaining_balance, 2) }}
                                        @elseif($isCompleted)
                                            <span style="color: #059669;">₱0.00</span>
                                        @else
                                            <span style="color: var(--text-muted); font-weight: normal;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isActive)
                                            <span style="font-weight: 600;">{{ $loan->days_paid_count }} / {{ $loan->term_days ?? 60 }}</span>
                                        @elseif($isCompleted)
                                            <span style="font-weight: 600; color: var(--text-secondary);">{{ $loan->term_days ?? 60 }} / {{ $loan->term_days ?? 60 }}</span>
                                        @else
                                            <span style="color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $c->last_payment_date ?? 'No payments yet' }}</td>
                                    <td>
                                        @if($pendingWalletTx)
                                            <button type="button" class="btn btn-sm btn-outline" style="opacity: 0.7; cursor: not-allowed; font-size: 11.5px; border-color: #f59e0b; color: #b45309; background: #fffbeb;" title="Payment locked while client has a pending transaction awaiting Host authorization">
                                                🔒 Payment Locked
                                            </button>
                                        @elseif($isActive)
                                            <a href="{{ route('collector.payments.collect_form', ['client_id' => $c->id]) }}" class="btn btn-sm btn-emerald">
                                                Collect Payment
                                            </a>
                                        @elseif($isApprovedForRelease)
                                            <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #bae6fd;">
                                                📦 Awaiting Finance
                                            </span>
                                        @elseif($isPendingLoan)
                                            <span class="badge badge-amber" style="background: #fef3c7; color: #b45309; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #fde68a;">
                                                ⏳ Pending Host Approval
                                            </span>
                                        @elseif($isCompleted)
                                            <span class="badge badge-emerald" style="background: #d1fae5; color: #065f46; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;">
                                                ✓ Completed
                                            </span>
                                        @else
                                            <span style="font-size: 12px; color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center" style="padding: 20px; color: var(--text-muted);">No assigned clients found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: RECENT COLLECTION RECORDS -->
    <div class="collector-tab-pane" id="col-tab-history">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📜 Recent Payments Collected</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Client Name</th>
                            <th>Amount Paid</th>
                            <th>Remaining Loan Balance</th>
                            <th>Status & Verification</th>
                            <th>Proof Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$recentPayments->isEmpty())
                            @foreach($recentPayments as $p)
                                <tr>
                                    <td>{{ $p->created_at->format('M d, Y h:i A') }}</td>
                                    <td><strong>{{ $p->client->user->name ?? 'N/A' }}</strong></td>
                                    <td style="font-weight: 700; color: #059669;">₱{{ number_format($p->amount_paid, 2) }}</td>
                                    <td style="font-weight: 600;">₱{{ number_format($p->client_remaining_balance_after, 2) }}</td>
                                    <td>
                                        @if($p->status === 'processing')
                                            <span class="badge badge-amber">⏳ Processing (Pending Remittance)</span>
                                        @else
                                            <span class="badge badge-emerald">✓ PAID</span>
                                            @if($p->adminVerifier)
                                                <div style="font-size: 10.5px; color: var(--text-muted); margin-top: 2px;">
                                                    Verified by: {{ $p->adminVerifier->name }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->proof_image_path)
                                            <a href="{{ asset($p->proof_image_path) }}" target="_blank" class="btn btn-sm btn-outline">
                                                View Proof
                                            </a>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 11.5px;">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 20px; color: var(--text-muted);">No recent collection records.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- REMIT COLLECTIONS TO ADMIN FINANCE MODAL -->
    <div class="modal-overlay" id="remitCollectionModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">💼 Remit Collections to Admin Finance</h3>
                <button type="button" class="modal-close" onclick="closeModal('remitCollectionModal')">&times;</button>
            </div>
            <form action="{{ route('collector.payments.remit') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                        <div style="font-size: 12px; color: #065f46; font-weight: 600;">Total Cash to Turn Over to Finance:</div>
                        <div style="font-size: 26px; font-weight: 800; color: #059669; margin: 2px 0;">₱{{ number_format($todayProcessingAmount, 2) }}</div>
                        <div style="font-size: 12px; color: #047857;">
                            {{ $pendingRemittanceCount }} client collections will be updated from <strong>Processing</strong> to <strong>PAID</strong>.
                        </div>
                    </div>

                    <div class="form-group" style="text-align: center;">
                        <label class="form-label" style="font-weight: 700; font-size: 13.5px; margin-bottom: 8px;">
                            🔒 Admin Finance 4-Digit Security PIN *
                        </label>
                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">
                            Ask Admin Finance Officer to enter their PIN code to receive and confirm this cash remittance.
                        </p>
                        <input type="password" name="admin_pin" maxlength="4" inputmode="numeric" class="form-control" placeholder="••••" required style="letter-spacing: 12px; font-size: 26px; text-align: center; max-width: 220px; font-weight: 800; margin: 0 auto;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('remitCollectionModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">✓ Confirm & Receive Remittance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Collector Cashout Modal -->
    <div class="modal-overlay" id="collectorCashoutModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Encash Collector Commission Balance</h3>
                <button type="button" class="modal-close" onclick="closeModal('collectorCashoutModal')">&times;</button>
            </div>
            <form action="{{ route('collector.cashout') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 12px; border-radius: var(--radius-md); margin-bottom: 14px;">
                        <div style="font-size: 11.5px; color: #065f46;">Available Commission Balance:</div>
                        <div style="font-size: 22px; font-weight: 700; color: #059669;">₱{{ number_format($collector->commission_balance, 2) }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Cash Out Amount (₱) *</label>
                        <input type="number" step="0.01" max="{{ $collector->commission_balance }}" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('collectorCashoutModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Submit Cashout Request</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function switchCollectorTab(tabId) {
            document.querySelectorAll('.collector-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.collector-tab-pane').forEach(pane => pane.classList.remove('active'));

            const targetPane = document.getElementById(tabId);
            const targetBtn = document.getElementById('btn-' + tabId);

            if (targetPane) targetPane.classList.add('active');
            if (targetBtn) targetBtn.classList.add('active');

            try {
                localStorage.setItem('active_collector_tab', tabId);
            } catch(e) {}
        }

        document.addEventListener('DOMContentLoaded', function() {
            const saved = localStorage.getItem('active_collector_tab');
            if (saved && document.getElementById(saved)) {
                switchCollectorTab(saved);
            }
        });
    </script>
@endpush
