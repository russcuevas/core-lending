@extends('layouts.app')

@section('title', 'Master Approvals Hub')
@section('page_title', 'Host Superadmin - Master Approvals Hub')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/host.css') }}">
@endpush

@section('content')
    <!-- Top Summary Approval KPI Cards with Real-Time Unread Pills -->
    <div class="approval-kpi-grid">
        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-loans')" style="position: relative;">
            @if($unreadLoansCount > 0)
                <span class="kpi-unread-pill" title="{{ $unreadLoansCount }} new unread loan applications">{{ $unreadLoansCount }}</span>
            @endif
            <div class="approval-kpi-icon" style="background: rgba(5, 150, 105, 0.12); color: #059669;">📋</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Pending Loans</div>
                <div class="approval-kpi-val">{{ $pendingLoans->count() }}</div>
                <div class="approval-kpi-sub">Total ₱{{ number_format($pendingLoans->sum('principal_amount'), 2) }}</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-wallet')" style="position: relative;">
            @if($unreadWalletCount > 0)
                <span class="kpi-unread-pill" title="{{ $unreadWalletCount }} new unread transactions">{{ $unreadWalletCount }}</span>
            @endif
            <div class="approval-kpi-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">💸</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Cash In / Out Requests</div>
                <div class="approval-kpi-val">{{ $pendingWalletRequests->count() }}</div>
                <div class="approval-kpi-sub">Total ₱{{ number_format($pendingWalletRequests->sum('amount'), 2) }}</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-collectors')" style="position: relative;">
            @if($unreadCollectorsCount > 0)
                <span class="kpi-unread-pill" title="{{ $unreadCollectorsCount }} new unread collector accounts">{{ $unreadCollectorsCount }}</span>
            @endif
            <div class="approval-kpi-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">🛵</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Collector Accounts</div>
                <div class="approval-kpi-val">{{ $pendingCollectors->count() }}</div>
                <div class="approval-kpi-sub">Pending verification</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-updates')" style="position: relative;">
            @if($unreadUpdatesCount > 0)
                <span class="kpi-unread-pill" title="{{ $unreadUpdatesCount }} new unread profile updates">{{ $unreadUpdatesCount }}</span>
            @endif
            <div class="approval-kpi-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">🔄</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Profile Updates</div>
                <div class="approval-kpi-val">{{ $pendingClientUpdates->count() }}</div>
                <div class="approval-kpi-sub">Client details changes</div>
            </div>
        </div>
    </div>

    <!-- Approval Category Switcher Tabs & Mark All Read Toolbar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
        <div class="approval-tabs-nav" style="margin-bottom: 0; border-bottom: none; padding-bottom: 0;">
            <button type="button" class="approval-tab-btn active" id="btn-tab-loans" onclick="switchApprovalTab('tab-loans')">
                <span>📋 Loan Applications</span>
                <span class="tab-badge">{{ $pendingLoans->count() }}</span>
                @if($unreadLoansCount > 0)
                    <span class="tab-badge-new" id="badge-tab-loans">{{ $unreadLoansCount }} new</span>
                @endif
            </button>
            <button type="button" class="approval-tab-btn" id="btn-tab-wallet" onclick="switchApprovalTab('tab-wallet')">
                <span>💸 Cash In / Out</span>
                <span class="tab-badge">{{ $pendingWalletRequests->count() }}</span>
                @if($unreadWalletCount > 0)
                    <span class="tab-badge-new" id="badge-tab-wallet">{{ $unreadWalletCount }} new</span>
                @endif
            </button>
            <button type="button" class="approval-tab-btn" id="btn-tab-collectors" onclick="switchApprovalTab('tab-collectors')">
                <span>🛵 Collector Accounts</span>
                <span class="tab-badge">{{ $pendingCollectors->count() }}</span>
                @if($unreadCollectorsCount > 0)
                    <span class="tab-badge-new" id="badge-tab-collectors">{{ $unreadCollectorsCount }} new</span>
                @endif
            </button>
            <button type="button" class="approval-tab-btn" id="btn-tab-updates" onclick="switchApprovalTab('tab-updates')">
                <span>🔄 Client Detail Updates</span>
                <span class="tab-badge">{{ $pendingClientUpdates->count() }}</span>
                @if($unreadUpdatesCount > 0)
                    <span class="tab-badge-new" id="badge-tab-updates">{{ $unreadUpdatesCount }} new</span>
                @endif
            </button>
            <button type="button" class="approval-tab-btn" id="btn-tab-all" onclick="switchApprovalTab('tab-all')">
                <span>👁️ View All Categories</span>
            </button>
        </div>

        @if($totalUnreadCount > 0)
            <div>
                <button type="button" class="btn btn-sm btn-outline" onclick="markAllAsRead('all')" style="font-size: 12px; padding: 6px 12px;" title="Mark all items as read">
                    ✓ Mark All as Read
                </button>
            </div>
        @endif
    </div>

    <!-- TAB 1: Pending Client Loans -->
    <div class="approval-tab-pane active" id="tab-loans">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📋 Pending Client Loan Applications ({{ $pendingLoans->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Superadmin authorization required before loan release and activation. Unread / new requests are pinned at the top.
                    </p>
                </div>
                <span class="badge badge-amber">Total: ₱{{ number_format($pendingLoans->sum('principal_amount'), 2) }}</span>
            </div>

            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Client Info</th>
                            <th>Principal</th>
                            <th>Loan Terms</th>
                            <th>Total Payable</th>
                            <th>Daily Installment</th>
                            <th>Assigned Collector</th>
                            <th>Encoded By</th>
                            <th>Valid ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$pendingLoans->isEmpty())
                            @foreach($pendingLoans as $loan)
                                <tr class="{{ !$loan->is_read ? 'unread-row' : '' }}" id="row-loan-{{ $loan->id }}">
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span>{{ $loan->client->user->name ?? 'Unknown' }}</span>
                                            @if(!$loan->is_read)
                                                <span class="badge-new pulse-effect" id="badge-loan-{{ $loan->id }}">🆕 New</span>
                                            @endif
                                            @if($loan->client && $loan->client->loans()->count() > 1)
                                                <span class="badge-reloan">🔄 Reloan</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                            📞 {{ $loan->client->user->phone_number ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 800; color: #059669; font-size: 14px;">
                                            ₱{{ number_format($loan->principal_amount, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-indigo">{{ $loan->interest_rate_percent }}% • 60 Days</span>
                                    </td>
                                    <td style="font-weight: 600;">₱{{ number_format($loan->total_payable, 2) }}</td>
                                    <td style="font-weight: 700; color: #d97706;">₱{{ number_format($loan->daily_installment, 2) }}/day</td>
                                    <td>
                                        <span class="badge badge-slate">{{ $loan->collector->user->name ?? 'Unassigned' }}</span>
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-secondary);">
                                        {{ $loan->encoder->name ?? 'Encoder' }}
                                    </td>
                                    <td>
                                        @if($loan->client && $loan->client->user && $loan->client->user->valid_id_path)
                                            <button type="button" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="previewIdImage('{{ asset($loan->client->user->valid_id_path) }}', '{{ $loan->client->user->name }}'); markItemAsRead('loan', {{ $loan->id }}, 'row-loan-{{ $loan->id }}');">
                                                🔍 View ID
                                            </button>
                                        @else
                                            <span style="font-size: 11.5px; color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: nowrap;">
                                            <form action="{{ route('host.approvals.loans.approve', $loan->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-emerald btn-sm" style="padding: 4px 10px; font-weight: 700;" title="Approve Loan Application">
                                                    ✓ Approve
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-rose btn-sm" style="padding: 4px 10px;" onclick="openDeclineModal('{{ route('host.approvals.loans.decline', $loan->id) }}', 'Loan for {{ $loan->client->user->name ?? 'Client' }}')" title="Decline Loan Application">
                                                ✕
                                            </button>
                                            @if(!$loan->is_read)
                                                <button type="button" class="btn-read-check" onclick="markItemAsRead('loan', {{ $loan->id }}, 'row-loan-{{ $loan->id }}')" title="Mark as read">
                                                    ✓
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="9" class="text-center" style="padding: 24px; color: var(--text-muted);">
                                    ✓ No pending loan applications waiting for approval.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Pending Cash In & Cash Out Requests -->
    <div class="approval-tab-pane" id="tab-wallet">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">💸 Cash-In & Cash-Out Transaction Approvals ({{ $pendingWalletRequests->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Disbursement authorizations reviewed and endorsed by Releasing Officers. Unread / new requests appear first.
                    </p>
                </div>
                <span class="badge badge-indigo">Total: ₱{{ number_format($pendingWalletRequests->sum('amount'), 2) }}</span>
            </div>

            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>User / Requester</th>
                            <th>Transaction Type</th>
                            <th>Request Amount</th>
                            <th>Reviewed By</th>
                            <th>Releasing Remarks</th>
                            <th>Scheduled Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$pendingWalletRequests->isEmpty())
                            @foreach($pendingWalletRequests as $req)
                                <tr class="{{ !$req->is_read ? 'unread-row' : '' }}" id="row-wallet-{{ $req->id }}">
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span>{{ $req->user->name }}</span>
                                            @if(!$req->is_read)
                                                <span class="badge-new pulse-effect" id="badge-wallet-{{ $req->id }}">🆕 New</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">
                                            📞 {{ $req->user->phone_number }} ({{ strtoupper($req->user->role) }})
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $req->type === 'cash_in' ? 'badge-emerald' : 'badge-amber' }}">
                                            {{ strtoupper(str_replace('_', ' ', $req->type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 800; font-size: 14px; color: {{ $req->type === 'cash_in' ? '#059669' : '#d97706' }};">
                                            ₱{{ number_format($req->amount, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-slate">{{ $req->releasingOfficer->name ?? 'Releasing Officer' }}</span>
                                    </td>
                                    <td style="font-size: 12px; max-width: 200px;">
                                        {{ $req->releasing_notes ?? '-' }}
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-secondary);">
                                        {{ $req->releasing_scheduled_date ?? 'Immediate' }}
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: nowrap;">
                                            <form action="{{ route('host.approvals.wallet.approve', $req->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-emerald btn-sm" style="padding: 4px 10px; font-weight: 700;">
                                                    ✓ Authorize
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-rose btn-sm" style="padding: 4px 10px;" onclick="openDeclineModal('{{ route('host.approvals.wallet.decline', $req->id) }}', 'Request for {{ $req->user->name }}')">
                                                ✕
                                            </button>
                                            @if(!$req->is_read)
                                                <button type="button" class="btn-read-check" onclick="markItemAsRead('wallet', {{ $req->id }}, 'row-wallet-{{ $req->id }}')" title="Mark as read">
                                                    ✓
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 24px; color: var(--text-muted);">
                                    ✓ No pending cash-in or cash-out requests.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Pending Collector Registrations -->
    <div class="approval-tab-pane" id="tab-collectors">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">🛵 Collector Account Registrations ({{ $pendingCollectors->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Field collector user access approvals and area assignment authorizations. Unread / new registrations listed first.
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Collector Name</th>
                            <th>Contact & Email</th>
                            <th>Assigned Area</th>
                            <th>Residential Address</th>
                            <th>Valid ID Proof</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$pendingCollectors->isEmpty())
                            @foreach($pendingCollectors as $colUser)
                                <tr class="{{ !$colUser->is_read ? 'unread-row' : '' }}" id="row-collector-{{ $colUser->id }}">
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span>{{ $colUser->name }}</span>
                                            @if(!$colUser->is_read)
                                                <span class="badge-new pulse-effect" id="badge-collector-{{ $colUser->id }}">🆕 New</span>
                                            @endif
                                        </div>
                                        <span class="badge badge-primary" style="font-size: 10.5px; margin-top: 3px;">New Collector</span>
                                    </td>
                                    <td>
                                        <div style="font-size: 12.5px;">📞 {{ $colUser->phone_number }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">✉️ {{ $colUser->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-slate">{{ $colUser->collector->assigned_area ?? 'General Area' }}</span>
                                    </td>
                                    <td style="font-size: 12px; max-width: 220px;">
                                        {{ $colUser->address ?? 'N/A' }}
                                    </td>
                                    <td>
                                        @if($colUser->valid_id_path)
                                            <button type="button" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="previewIdImage('{{ asset($colUser->valid_id_path) }}', '{{ $colUser->name }}'); markItemAsRead('collector', {{ $colUser->id }}, 'row-collector-{{ $colUser->id }}');">
                                                🔍 View ID
                                            </button>
                                        @else
                                            <span style="font-size: 11.5px; color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: nowrap;">
                                            <form action="{{ route('host.approvals.collectors.approve', $colUser->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-emerald btn-sm" style="padding: 4px 10px; font-weight: 700;">
                                                    ✓ Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('host.approvals.collectors.decline', $colUser->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-rose btn-sm" style="padding: 4px 10px;">
                                                    ✕ Decline
                                                </button>
                                            </form>
                                            @if(!$colUser->is_read)
                                                <button type="button" class="btn-read-check" onclick="markItemAsRead('collector', {{ $colUser->id }}, 'row-collector-{{ $colUser->id }}')" title="Mark as read">
                                                    ✓
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 24px; color: var(--text-muted);">
                                    ✓ No pending collector accounts.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 4: Client Detail Update Requests -->
    <div class="approval-tab-pane" id="tab-updates">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">🔄 Client Details Update Requests ({{ $pendingClientUpdates->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Profile changes submitted by Encoders requiring Superadmin verification. Unread / new requests appear at the top.
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table data-table-enhanced">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Requested By</th>
                            <th>Old Information</th>
                            <th>New Requested Information</th>
                            <th>Reason / Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(!$pendingClientUpdates->isEmpty())
                            @foreach($pendingClientUpdates as $update)
                                <tr class="{{ !$update->is_read ? 'unread-row' : '' }}" id="row-update-{{ $update->id }}">
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <span>{{ $update->client->user->name ?? 'N/A' }}</span>
                                            @if(!$update->is_read)
                                                <span class="badge-new pulse-effect" id="badge-update-{{ $update->id }}">🆕 New</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $update->requester->name ?? 'Admin' }}</td>
                                    <td>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">Name: {{ $update->old_data['name'] ?? '' }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">Phone: {{ $update->old_data['phone_number'] ?? '' }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">Address: {{ $update->old_data['address'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px; color: #059669; font-weight: 600;">Name: {{ $update->new_data['name'] ?? '' }}</div>
                                        <div style="font-size: 12px; color: #059669; font-weight: 600;">Phone: {{ $update->new_data['phone_number'] ?? '' }}</div>
                                        <div style="font-size: 12px; color: #059669; font-weight: 600;">Address: {{ $update->new_data['address'] ?? '' }}</div>
                                    </td>
                                    <td style="font-size: 12px; max-width: 180px;">{{ $update->notes ?? 'N/A' }}</td>
                                    <td>
                                        <div style="display: flex; gap: 4px; align-items: center; flex-wrap: nowrap;">
                                            <form action="{{ route('host.approvals.client_updates.approve', $update->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-emerald" style="padding: 4px 10px; font-weight: 700;">✓ Approve</button>
                                            </form>
                                            <form action="{{ route('host.approvals.client_updates.decline', $update->id) }}" method="POST" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-rose" style="padding: 4px 10px;">✕ Decline</button>
                                            </form>
                                            @if(!$update->is_read)
                                                <button type="button" class="btn-read-check" onclick="markItemAsRead('update', {{ $update->id }}, 'row-update-{{ $update->id }}')" title="Mark as read">
                                                    ✓
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 24px; color: var(--text-muted);">
                                    ✓ No client profile change requests.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal-overlay" id="imagePreviewModal">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="imagePreviewTitle">Valid ID Preview</h3>
                <button type="button" class="modal-close" onclick="closeModal('imagePreviewModal')">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 16px;">
                <img id="previewImageTarget" src="" alt="Valid ID" style="max-width: 100%; max-height: 450px; border-radius: 8px; box-shadow: var(--shadow-md); display: inline-block;">
            </div>
            <div class="modal-footer">
                <a id="previewImageDownloadBtn" href="" target="_blank" class="btn btn-outline">Open Full Image in New Tab</a>
                <button type="button" class="btn btn-primary" onclick="closeModal('imagePreviewModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Generic Decline Modal -->
    <div class="modal-overlay" id="declineModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="declineModalTitle">Decline Request</h3>
                <button type="button" class="modal-close" onclick="closeModal('declineModal')">&times;</button>
            </div>
            <form id="declineForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">State Reason for Declining *</label>
                        <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="4" placeholder="Enter clear explanation for audit logging..." required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('declineModal')">Cancel</button>
                    <button type="submit" class="btn btn-rose">Confirm Decline</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openDeclineModal(actionUrl, itemName) {
            document.getElementById('declineForm').action = actionUrl;
            document.getElementById('declineModalTitle').innerText = 'Decline: ' + itemName;
            openModal('declineModal');
        }

        function previewIdImage(imageSrc, clientName) {
            document.getElementById('previewImageTarget').src = imageSrc;
            document.getElementById('previewImageDownloadBtn').href = imageSrc;
            document.getElementById('imagePreviewTitle').innerText = 'Valid ID Proof: ' + clientName;
            openModal('imagePreviewModal');
        }

        function switchApprovalTab(tabId, updateUrl = true) {
            if (!tabId) tabId = 'tab-loans';

            // Remove active from all tabs
            document.querySelectorAll('.approval-tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.approval-tab-pane').forEach(pane => pane.classList.remove('active'));

            if (tabId === 'tab-all') {
                document.querySelectorAll('.approval-tab-pane').forEach(pane => pane.classList.add('active'));
                const btnAll = document.getElementById('btn-tab-all');
                if (btnAll) btnAll.classList.add('active');
            } else {
                const targetPane = document.getElementById(tabId);
                const targetBtn = document.getElementById('btn-' + tabId);
                if (targetPane) targetPane.classList.add('active');
                if (targetBtn) targetBtn.classList.add('active');
            }

            try {
                localStorage.setItem('active_approval_tab', tabId);
                if (updateUrl && window.history && window.history.replaceState) {
                    window.history.replaceState(null, null, '#' + tabId);
                }
            } catch(e) {}
        }

        function getInitialApprovalTab() {
            const validTabs = ['tab-loans', 'tab-wallet', 'tab-collectors', 'tab-updates', 'tab-all'];
            const hash = window.location.hash ? window.location.hash.replace('#', '') : '';
            if (hash && validTabs.includes(hash)) {
                return hash;
            }
            const saved = localStorage.getItem('active_approval_tab');
            if (saved && validTabs.includes(saved)) {
                return saved;
            }
            return 'tab-loans';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = getInitialApprovalTab();
            switchApprovalTab(activeTab, false);
        });

        window.addEventListener('hashchange', function() {
            const hash = window.location.hash.replace('#', '');
            const validTabs = ['tab-loans', 'tab-wallet', 'tab-collectors', 'tab-updates', 'tab-all'];
            if (hash && validTabs.includes(hash)) {
                switchApprovalTab(hash, false);
            }
        });

        function markItemAsRead(type, id, rowId) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch("{{ route('host.approvals.mark_read') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type: type, id: id })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    const row = document.getElementById(rowId);
                    if (row) {
                        row.classList.remove('unread-row');
                        const badge = row.querySelector('.badge-new');
                        if (badge) badge.remove();
                        const checkBtn = row.querySelector('.btn-read-check');
                        if (checkBtn) checkBtn.remove();
                    }
                }
            }).catch(err => console.error('Error marking as read:', err));
        }

        function markAllAsRead(type) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch("{{ route('host.approvals.mark_read') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type: type })
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    window.location.reload();
                }
            }).catch(err => {
                window.location.reload();
            });
        }
    </script>
@endpush
