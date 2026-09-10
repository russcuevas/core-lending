@extends('layouts.app')

@section('title', 'Admin Finance Operations Hub')
@section('page_title', 'Admin Finance - Cash Management & Remittance Operations')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
    <style>
        .finance-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }
        .finance-kpi-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 16px 18px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: relative;
            transition: all var(--transition-fast);
        }
        .finance-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .finance-kpi-label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .finance-kpi-val {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
        }
        .remittance-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-left: 4px solid #059669;
            border-radius: var(--radius-md);
            padding: 16px 18px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            transition: all var(--transition-fast);
        }
        .remittance-card:hover {
            box-shadow: var(--shadow-md);
        }
    </style>
@endpush

@section('content')
    <!-- Top Financial KPI Summary Cards -->
    <div class="finance-kpi-grid">
        <div class="finance-kpi-card" style="border-left: 4px solid #059669;">
            <div class="finance-kpi-label">Remitted Cash on Hand</div>
            <div class="finance-kpi-val" style="color: #059669;">₱{{ number_format($cashOnHand, 2) }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Available for turnover to Host</div>
            <div style="margin-top: 10px;">
                <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('cashTurnoverModal')" style="font-weight: 700; width: 100%;">
                    💵 Turn Over Cash to Host
                </button>
            </div>
        </div>

        <div class="finance-kpi-card" style="border-left: 4px solid #d97706;">
            <div class="finance-kpi-label">Pending Collector Remittances</div>
            <div class="finance-kpi-val" style="color: #d97706;">₱{{ number_format($totalPendingRemittanceAmount, 2) }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">{{ $pendingRemittances->count() }} field collections awaiting PIN</div>
        </div>

        <div class="finance-kpi-card" style="border-left: 4px solid #0284c7;">
            <div class="finance-kpi-label">Total Remitted Today</div>
            <div class="finance-kpi-val" style="color: #0284c7;">₱{{ number_format($totalRemittedToday, 2) }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Verified collections received</div>
        </div>

        <div class="finance-kpi-card" style="border-left: 4px solid #7c3aed;">
            <div class="finance-kpi-label">Ready for Release</div>
            <div class="finance-kpi-val" style="color: #7c3aed;">{{ $approvedReadyToRelease->count() + $loansReadyToDisburse->count() }}</div>
            <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">Disbursements approved by Host</div>
        </div>
    </div>

    <!-- SECTION 1: Collector Cash Remittance Receiving (Awaiting Admin Finance PIN) -->
    <div class="card" style="border-top: 3px solid #059669;">
        <div class="card-header">
            <div>
                <h3 class="card-title" style="color: #059669;">
                    <span>📥</span> Collector Field Remittances Awaiting Verification ({{ $collectorsWithPendingRemittances->count() }} Collectors)
                </h3>
                <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                    Collectors who submitted cash collections in the field. Enter Admin Finance PIN to verify and update client statuses to <strong>PAID</strong>.
                </p>
            </div>
            <span class="badge badge-emerald">Total: ₱{{ number_format($totalPendingRemittanceAmount, 2) }}</span>
        </div>

        @if($collectorsWithPendingRemittances->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ All field collections are currently remitted and verified.
            </div>
        @else
            @foreach($collectorsWithPendingRemittances as $collectorId => $payments)
                @php
                    $collector = $payments->first()->collector;
                    $collectorTotal = $payments->sum('amount_paid');
                    $loanPart = $payments->sum('loan_premium_amount');
                    $insPart = $payments->sum('insurance_premium_amount');
                @endphp
                <div class="remittance-card">
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <h4 style="font-size: 15.5px; margin: 0; font-weight: 700; color: #0f172a;">
                                🛵 {{ $collector->user->name ?? 'Collector #' . $collectorId }}
                            </h4>
                            <span class="badge badge-slate" style="font-size: 11px;">Area: {{ $collector->assigned_area ?? 'General Area' }}</span>
                            <span class="badge badge-amber" style="font-size: 11px;">{{ $payments->count() }} Collections</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                            Breakdown: Loan Principal/Interest: ₱{{ number_format($loanPart, 2) }} | Insurance: ₱{{ number_format($insPart, 2) }}
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <div style="text-align: right;">
                            <div style="font-size: 20px; font-weight: 800; color: #059669;">
                                ₱{{ number_format($collectorTotal, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">Total Remittance Amount</div>
                        </div>

                        <button type="button" class="btn btn-emerald" onclick="openReceiveRemittanceModal('{{ $collector->id }}', '{{ $collector->user->name ?? 'Collector' }}', '{{ number_format($collectorTotal, 2) }}', '{{ $payments->count() }}')">
                            🔒 Verify PIN & Receive Remittance
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- SECTION 2: Approved by Host - Ready for Physical Release (PIN & Camera Photo Proof Required) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title" style="color: #059669;">
                <span>🎯</span> Ready for Physical Execution (Approved by Host) ({{ $approvedReadyToRelease->count() + $loansReadyToDisburse->count() }})
            </h3>
            <span class="badge badge-emerald">Requires Client PIN & Timestamp Photo</span>
        </div>

        @if($approvedReadyToRelease->isEmpty() && $loansReadyToDisburse->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                ✓ No approved items awaiting physical execution at this moment.
            </div>
        @else
            <!-- Wallet Cash In / Out execution cards -->
            @foreach($approvedReadyToRelease as $tx)
                <div class="releasing-request-card" style="border-left-color: #059669;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge {{ $tx->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}" style="margin-bottom: 4px;">
                                {{ strtoupper(str_replace('_', ' ', $tx->type)) }} APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0;">{{ $tx->user->name }} ({{ $tx->user->role }})</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Contact No: {{ $tx->user->phone_number }} | Address: {{ $tx->user->address }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: {{ $tx->type === 'cash_in' ? '#059669' : '#d97706' }};">
                                ₱{{ number_format($tx->amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">Host Approved</div>
                        </div>
                    </div>

                    <div class="request-meta-grid">
                        <div class="request-item">
                            <span class="request-label">Scheduled Date:</span>
                            <span class="request-value">{{ $tx->releasing_scheduled_date ?? 'Today' }}</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Finance Notes:</span>
                            <span class="request-value">{{ $tx->releasing_notes ?? 'None' }}</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Host Approver Notes:</span>
                            <span class="request-value">{{ $tx->host_notes ?? 'Approved' }}</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.requests.execute', $tx->id) }}', '{{ strtoupper(str_replace('_', ' ', $tx->type)) }}', '{{ $tx->user->name }}', '{{ number_format($tx->amount, 2) }}')">
                            📷 Verify PIN & Capture Photo Proof
                        </button>
                    </div>
                </div>
            @endforeach

            <!-- Loan Principal Releases -->
            @foreach($loansReadyToDisburse as $loan)
                <div class="releasing-request-card" style="border-left-color: #4f46e5;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge badge-indigo" style="margin-bottom: 4px;">
                                LOAN PRINCIPAL DISBURSEMENT APPROVED BY HOST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0;">{{ $loan->client->user->name }}</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">Contact No: {{ $loan->client->user->phone_number }} | Collector: {{ $loan->collector->user->name ?? 'None' }}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: #4f46e5;">
                                ₱{{ number_format($loan->principal_amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">Principal Cash to Disburse</div>
                        </div>
                    </div>

                    <div class="request-meta-grid">
                        <div class="request-item">
                            <span class="request-label">Total Payable:</span>
                            <span class="request-value">₱{{ number_format($loan->total_payable, 2) }} (60 Days)</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Daily Installment:</span>
                            <span class="request-value">₱{{ number_format($loan->daily_installment, 2) }} / day</span>
                        </div>
                        <div class="request-item">
                            <span class="request-label">Host Approver:</span>
                            <span class="request-value">{{ $loan->hostApprover->name ?? 'Superadmin' }}</span>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald" onclick="openDisburseModal('{{ route('admin.releasing.loans.disburse', $loan->id) }}', 'LOAN PRINCIPAL DISBURSEMENT', '{{ $loan->client->user->name }}', '{{ number_format($loan->principal_amount, 2) }}')">
                            📷 Disburse Loan Funds & Capture Photo Proof
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- SECTION 3: Pending Initial Review Requests from Clients / Collectors -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>📩</span> Incoming Requests Waiting for Finance Review ({{ $pendingReviews->count() }})
            </h3>
            <span class="badge badge-amber">Step 1: Review & Submit to Host</span>
        </div>

        @if($pendingReviews->isEmpty())
            <div style="padding: 20px; text-align: center; color: var(--text-secondary);">
                ✓ No new incoming requests.
            </div>
        @else
            @foreach($pendingReviews as $req)
                <div class="releasing-request-card urgent">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <span class="badge {{ $req->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}">
                                {{ strtoupper(str_replace('_', ' ', $req->type)) }} REQUEST
                            </span>
                            <h4 style="font-size: 15px; margin: 4px 0 2px 0;">{{ $req->user->name }}</h4>
                            <div style="font-size: 12.5px; color: var(--text-secondary);">
                                Role: {{ ucfirst($req->user->role) }} | Contact No: {{ $req->user->phone_number }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: #0f172a;">
                                ₱{{ number_format($req->amount, 2) }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted);">{{ $req->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: var(--radius-sm); margin: 10px 0; font-size: 12.5px;">
                        <strong>User Remarks:</strong> {{ $req->releasing_notes ?? 'None provided' }}
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-emerald btn-sm" onclick="openReviewModal('{{ route('admin.releasing.requests.review', $req->id) }}', 'approve', '{{ $req->user->name }}', '{{ number_format($req->amount, 2) }}')">
                            ✓ Approve & Submit to Host
                        </button>
                        <button type="button" class="btn btn-rose btn-sm" onclick="openReviewModal('{{ route('admin.releasing.requests.review', $req->id) }}', 'decline', '{{ $req->user->name }}', '{{ number_format($req->amount, 2) }}')">
                            ✕ Decline Request
                        </button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <!-- SECTION 4: Cash Turnovers to Host Superadmin -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💵 Cash Turnovers to Host Superadmin</h3>
            <button type="button" class="btn btn-emerald btn-sm" onclick="openModal('cashTurnoverModal')">
                + New Cash Turnover
            </button>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Turnover Ref</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Loan Premium</th>
                        <th>Insurance Premium</th>
                        <th>Status</th>
                        <th>Host Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$recentTurnovers->isEmpty())
                        @foreach($recentTurnovers as $to)
                            <tr>
                                <td><strong>{{ $to->turnover_reference }}</strong></td>
                                <td>{{ $to->date->format('M d, Y') }}</td>
                                <td style="font-weight: 800; color: #059669;">₱{{ number_format($to->total_amount, 2) }}</td>
                                <td>₱{{ number_format($to->loan_collection_amount, 2) }}</td>
                                <td style="color: #0284c7;">₱{{ number_format($to->insurance_collection_amount, 2) }}</td>
                                <td>
                                    @if($to->status === 'approved')
                                        <span class="badge badge-emerald">✓ Approved & Received</span>
                                    @elseif($to->status === 'pending_host_approval')
                                        <span class="badge badge-amber">⏳ Pending Host Approval</span>
                                    @else
                                        <span class="badge badge-rose">✕ Declined</span>
                                    @endif
                                </td>
                                <td style="font-size: 12px; color: var(--text-secondary);">
                                    {{ $to->host_notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 20px; color: var(--text-muted);">
                                No cash turnover records found.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECEIVE REMITTANCE PIN MODAL -->
    <div class="modal-overlay" id="receiveRemittanceModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">📥 Receive Collector Remittance</h3>
                <button type="button" class="modal-close" onclick="closeModal('receiveRemittanceModal')">&times;</button>
            </div>
            <form action="{{ route('admin.releasing.remittances.receive') }}" method="POST">
                @csrf
                <input type="hidden" name="collector_id" id="remittanceCollectorId">

                <div class="modal-body">
                    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; padding: 14px; border-radius: var(--radius-md); margin-bottom: 16px;">
                        <h4 style="font-size: 15px; font-weight: 700; color: #065f46; margin: 0 0 4px 0;" id="remitCollectorName">Collector Name</h4>
                        <div style="font-size: 24px; font-weight: 800; color: #059669;" id="remitTotalAmount">₱0.00</div>
                        <div style="font-size: 12px; color: #047857; margin-top: 2px;" id="remitCountText">0 collections ready to verify</div>
                    </div>

                    <div class="form-group" style="text-align: center;">
                        <label class="form-label" style="font-weight: 700; font-size: 13.5px; margin-bottom: 6px;">
                            🔒 Enter Your Admin Finance 4-Digit Security PIN *
                        </label>
                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 12px;">
                            Entering your PIN verifies the physical cash received and updates client loan accounts to <strong>PAID</strong>.
                        </p>
                        <input type="password" name="admin_pin" maxlength="4" inputmode="numeric" class="form-control" placeholder="••••" required style="letter-spacing: 12px; font-size: 26px; text-align: center; max-width: 220px; font-weight: 800; margin: 0 auto;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('receiveRemittanceModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">✓ Confirm Receipt & Update to PAID</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CASH TURNOVER TO HOST MODAL -->
    <div class="modal-overlay" id="cashTurnoverModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">💵 Submit Cash Turnover to Host Superadmin</h3>
                <button type="button" class="modal-close" onclick="closeModal('cashTurnoverModal')">&times;</button>
            </div>
            <form action="{{ route('admin.releasing.turnover.submit') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 14px; border-radius: var(--radius-md); margin-bottom: 14px;">
                        <div style="font-size: 12px; color: #166534; font-weight: 600;">Remitted Cash on Hand Available:</div>
                        <div style="font-size: 24px; font-weight: 800; color: #15803d;">₱{{ number_format($cashOnHand, 2) }}</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Turnover Amount to Hand Over (₱) *</label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control" value="{{ $cashOnHand > 0 ? $cashOnHand : '' }}" placeholder="0.00" required style="font-size: 17px; font-weight: 700; color: #059669;">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Turnover Notes / Remarks</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Daily collections turnover from collectors."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('cashTurnoverModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">Submit Turnover to Host</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review & Submit to Host Modal -->
    <div class="modal-overlay" id="reviewModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="reviewModalTitle">Review Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
            </div>
            <form id="reviewForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="reviewAction">

                <div class="modal-body">
                    <div id="approveFields">
                        <div class="form-group">
                            <label class="form-label">Scheduled Target Release / Collection Date</label>
                            <input type="date" name="scheduled_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes for Superadmin / Host</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="e.g. Client verified in person. Recommended for approval."></textarea>
                        </div>
                    </div>

                    <div id="declineFields" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Reason for Decline *</label>
                            <textarea name="decline_reason" id="decline_reason" class="form-control" rows="3" placeholder="State reason to inform the client..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" id="reviewSubmitBtn">Submit to Host</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Physical Execution Modal with Live Camera & Date/Time Stamp Overlay -->
    <div class="modal-overlay" id="disburseModal">
        <div class="modal-box modal-lg" style="max-width: 620px;">
            <div class="modal-header">
                <h3 class="modal-title" id="disburseModalTitle">Execute Physical Transaction</h3>
                <button type="button" class="modal-close" onclick="closeDisburseModal()">&times;</button>
            </div>
            <form id="disburseForm" method="POST" onsubmit="return validateDisbursementForm()">
                @csrf
                <input type="hidden" name="photo_proof" id="photoProofInput" required>

                <div class="modal-body" style="padding: 16px;">
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-md); margin-bottom: 14px;">
                        <h4 style="font-size: 14.5px; margin-bottom: 2px; font-weight: 700; color: var(--text-primary);" id="disburseSummaryTitle">Transaction Details</h4>
                        <div style="font-size: 12px; color: var(--text-secondary);">
                            Enter client 4-digit PIN & capture real-time verified photo proof with automatic timestamp watermark.
                        </div>
                    </div>

                    <!-- Client PIN Input -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; display: flex; justify-content: space-between;">
                            <span>Client 4-Digit PIN Code *</span>
                            <span style="font-size: 11.5px; color: var(--text-muted); font-weight: 400;">Ask borrower to enter</span>
                        </label>
                        <input type="password" name="client_pin" id="releasing_client_pin" maxlength="4" inputmode="numeric" class="form-control" placeholder="••••" required style="letter-spacing: 10px; font-size: 24px; text-align: center; max-width: 220px; font-weight: 700; margin: 0 auto;">
                    </div>

                    <!-- Camera Section -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-weight: 700; font-size: 13px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span>Photo Proof (with Auto Timestamp) *</span>
                            <span style="font-size: 11.5px; color: #059669; font-weight: 600;">Live Camera Ready</span>
                        </label>

                        <!-- Option 1: Live Video Camera Viewport -->
                        <div id="camera-stream-wrapper" style="position: relative;">
                            <div class="camera-box-viewport">
                                <!-- Floating Quick Actions Bar -->
                                <div class="camera-top-toolbar">
                                    <button type="button" class="camera-tool-pill" onclick="flipReleasingCamera('webcamVideo')" title="Switch Front/Back Camera">
                                        🔄 Flip Camera
                                    </button>
                                    <button type="button" class="camera-tool-pill" id="releasing-torch-btn" onclick="toggleReleasingTorch()" style="display: none;" title="Toggle Flashlight">
                                        💡 Flash
                                    </button>
                                </div>

                                <video id="webcamVideo" autoplay playsinline muted></video>
                                <canvas id="proofCanvas" style="display: none;"></canvas>

                                <!-- Big Mobile Camera Shutter Button -->
                                <div class="camera-shutter-bar">
                                    <button type="button" class="camera-shutter-btn" onclick="captureSnapshotWithTimestamp('webcamVideo', 'proofCanvas', 'photoProofInput', 'proofPreviewImg')" title="Take Photo">
                                        <div class="camera-shutter-btn-inner">📸</div>
                                    </button>
                                </div>
                            </div>

                            <!-- Native Phone Camera / Photo Upload Fallback Bar -->
                            <div style="text-align: center; margin-top: 10px;">
                                <label class="btn btn-outline btn-sm" style="font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                    <span>📱 Open Phone Camera App / Upload Photo</span>
                                    <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'proofCanvas', 'photoProofInput', 'proofPreviewImg')">
                                </label>
                            </div>
                        </div>

                        <!-- Option 2: Captured Photo Preview & Retake Bar -->
                        <div id="photo-preview-wrapper" style="display: none;">
                            <div class="photo-preview-card">
                                <img id="proofPreviewImg" src="" alt="Captured Proof Preview">
                                <div class="photo-preview-actions">
                                    <button type="button" class="btn btn-sm btn-outline" onclick="retakeReleasingPhoto('webcamVideo', 'proofPreviewImg', 'photoProofInput')" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3);">
                                        🔄 Retake Photo
                                    </button>
                                    <label class="btn btn-sm btn-outline" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3); cursor: pointer;">
                                        📁 Choose Different Photo
                                        <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'proofCanvas', 'photoProofInput', 'proofPreviewImg')">
                                    </label>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #059669; font-weight: 700; text-align: center; margin-top: 8px;">
                                ✓ Photo Stamped with Verified Timestamp and Ready for Submission.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="padding: 12px 16px;">
                    <button type="button" class="btn btn-outline" onclick="closeDisburseModal()">Cancel</button>
                    <button type="submit" class="btn btn-emerald" id="submitDisbursementBtn" style="font-weight: 700;">
                        ✓ Complete & Release Cash
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/admin.js') }}"></script>
    <script>
        function openReceiveRemittanceModal(collectorId, collectorName, amount, count) {
            document.getElementById('remittanceCollectorId').value = collectorId;
            document.getElementById('remitCollectorName').innerText = '🛵 ' + collectorName;
            document.getElementById('remitTotalAmount').innerText = '₱' + amount;
            document.getElementById('remitCountText').innerText = count + ' field collections ready to verify & update to PAID';
            openModal('receiveRemittanceModal');
        }

        function openReviewModal(actionUrl, actionType, userName, amount) {
            document.getElementById('reviewForm').action = actionUrl;
            document.getElementById('reviewAction').value = actionType;

            if (actionType === 'approve') {
                document.getElementById('reviewModalTitle').innerText = 'Approve & Submit to Host (' + userName + ' - ₱' + amount + ')';
                document.getElementById('approveFields').style.display = 'block';
                document.getElementById('declineFields').style.display = 'none';
                document.getElementById('reviewSubmitBtn').className = 'btn btn-emerald';
                document.getElementById('reviewSubmitBtn').innerText = 'Forward to Host for Approval';
            } else {
                document.getElementById('reviewModalTitle').innerText = 'Decline Request (' + userName + ' - ₱' + amount + ')';
                document.getElementById('approveFields').style.display = 'none';
                document.getElementById('declineFields').style.display = 'block';
                document.getElementById('reviewSubmitBtn').className = 'btn btn-rose';
                document.getElementById('reviewSubmitBtn').innerText = 'Confirm Decline';
            }
            openModal('reviewModal');
        }

        function openDisburseModal(actionUrl, typeName, clientName, amount) {
            document.getElementById('disburseForm').action = actionUrl;
            document.getElementById('disburseModalTitle').innerText = typeName + ' - ' + clientName;
            document.getElementById('disburseSummaryTitle').innerText = clientName + ' | Amount: ₱' + amount;
            document.getElementById('photoProofInput').value = '';
            document.getElementById('releasing_client_pin').value = '';
            
            document.getElementById('camera-stream-wrapper').style.display = 'block';
            document.getElementById('photo-preview-wrapper').style.display = 'none';
            document.getElementById('proofPreviewImg').src = '';

            openModal('disburseModal');
            startCamera('webcamVideo');
        }

        function closeDisburseModal() {
            stopCamera();
            closeModal('disburseModal');
        }

        function validateDisbursementForm() {
            const photoInput = document.getElementById('photoProofInput');
            const pinInput = document.getElementById('releasing_client_pin');

            if (!pinInput.value || pinInput.value.length !== 4) {
                showToast('error', 'Please enter a valid 4-digit client PIN.');
                pinInput.focus();
                return false;
            }

            if (!photoInput.value) {
                showToast('error', 'Please capture or upload photo proof before submitting.');
                return false;
            }

            return true;
        }
    </script>
@endpush
