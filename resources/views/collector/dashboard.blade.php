@extends('layouts.app')

@section('title', 'Collector Portal')
@section('page_title', 'Collector - Field Portal & Commission Wallet')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/collector.css') }}">
@endpush

@section('content')
    <!-- Commission Balance Hero Card -->
    <div class="collector-hero-balance">
        <div>
            <div class="collector-balance-title">My Total Accumulated Commission Balance</div>
            <div class="collector-balance-val">₱{{ number_format($collector->commission_balance, 2) }}</div>
            <div class="collector-commission-rates">
                <span class="commission-badge">₱300 per Fully-Paid Loan</span>
                <span class="commission-badge">5% Automated on Client Savings</span>
            </div>
        </div>

        <div>
            <button type="button" class="btn btn-primary" style="background: #ffffff; color: #065f46; font-weight: 700; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.15);" onclick="openModal('collectorCashoutModal')">
                💰 Encash Commission
            </button>
        </div>
    </div>

    <!-- Delinquency Warning Alert (3 Consecutive Missed Days) -->
    @if($delinquentClients->isNotEmpty())
        <div class="delinquent-alert-banner">
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

    <!-- Quick Actions -->
    <div style="margin-bottom: 16px;">
        <a href="{{ route('collector.scan_qr') }}" class="btn btn-emerald" style="width: 100%; max-width: 360px; padding: 10px 16px;">
            📷 Scan Client QR Code / Collect Daily Payment
        </a>
    </div>

    <!-- Assigned Clients List -->
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
                        <th>Daily Due</th>
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
                                $isPending = ($c->status === 'pending_host_approval' || $c->status === 'pending') || ($loan && in_array($loan->status, ['pending_host_approval', 'pending_releasing_review', 'pending_approval', 'pending', 'approved_for_release', 'ready_for_release']));
                                $isCompleted = $loan && $loan->status === 'completed';
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $c->user->name }}</strong>
                                    <div style="font-size: 11.5px; color: var(--text-secondary);">{{ $c->user->address }}</div>
                                </td>
                                <td>{{ $c->user->phone_number }}</td>
                                <td>
                                    @if($isActive)
                                        <span class="badge badge-emerald">₱{{ number_format($loan->principal_amount, 2) }}</span>
                                    @elseif($isPending)
                                        <span class="badge badge-amber" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">⏳ Pending Host Approval</span>
                                    @else
                                        <span class="badge badge-slate">No Active Loan</span>
                                    @endif
                                </td>
                                <td style="font-weight: 700; color: #d97706;">
                                    @if($isActive)
                                        ₱{{ number_format($loan->daily_installment, 2) }}
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
                                    @if($isActive)
                                        <a href="{{ route('collector.payments.collect_form', ['client_id' => $c->id]) }}" class="btn btn-sm btn-emerald">
                                            Collect Payment
                                        </a>
                                    @elseif($isPending)
                                        <span class="badge badge-amber" style="background: #fef3c7; color: #b45309; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #fde68a;">⏳ Pending Host Approval</span>
                                    @elseif($isCompleted)
                                        <span class="badge badge-emerald" style="background: #d1fae5; color: #065f46; font-size: 11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;">✓ Completed</span>
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

    <!-- Recent Collections -->
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
                            <td colspan="5" class="text-center" style="padding: 20px; color: var(--text-muted);">No recent collection records.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
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
