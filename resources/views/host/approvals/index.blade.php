@extends('layouts.app')

@section('title', 'Master Approvals Hub')
@section('page_title', 'Host Superadmin - Master Approvals Hub')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <!-- Top Summary Approval KPI Cards -->
    <div class="approval-kpi-grid">
        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-loans')">
            <div class="approval-kpi-icon" style="background: rgba(5, 150, 105, 0.12); color: #059669;">📋</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Pending Loans</div>
                <div class="approval-kpi-val">{{ $pendingLoans->count() }}</div>
                <div class="approval-kpi-sub">Total ₱{{ number_format($pendingLoans->sum('principal_amount'), 2) }}</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-wallet')">
            <div class="approval-kpi-icon" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">💸</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Cash In / Out Requests</div>
                <div class="approval-kpi-val">{{ $pendingWalletRequests->count() }}</div>
                <div class="approval-kpi-sub">Total ₱{{ number_format($pendingWalletRequests->sum('amount'), 2) }}</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-collectors')">
            <div class="approval-kpi-icon" style="background: rgba(124, 58, 237, 0.12); color: #7c3aed;">🛵</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Collector Accounts</div>
                <div class="approval-kpi-val">{{ $pendingCollectors->count() }}</div>
                <div class="approval-kpi-sub">Pending verification</div>
            </div>
        </div>

        <div class="approval-kpi-card" onclick="switchApprovalTab('tab-updates')">
            <div class="approval-kpi-icon" style="background: rgba(217, 119, 6, 0.12); color: #d97706;">🔄</div>
            <div class="approval-kpi-info">
                <div class="approval-kpi-title">Profile Updates</div>
                <div class="approval-kpi-val">{{ $pendingClientUpdates->count() }}</div>
                <div class="approval-kpi-sub">Client details changes</div>
            </div>
        </div>
    </div>

    <!-- Approval Category Switcher Tabs -->
    <div class="approval-tabs-nav">
        <button type="button" class="approval-tab-btn active" id="btn-tab-loans" onclick="switchApprovalTab('tab-loans')">
            <span>📋 Loan Applications</span>
            <span class="tab-badge">{{ $pendingLoans->count() }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="btn-tab-wallet" onclick="switchApprovalTab('tab-wallet')">
            <span>💸 Cash In / Out</span>
            <span class="tab-badge">{{ $pendingWalletRequests->count() }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="btn-tab-collectors" onclick="switchApprovalTab('tab-collectors')">
            <span>🛵 Collector Accounts</span>
            <span class="tab-badge">{{ $pendingCollectors->count() }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="btn-tab-updates" onclick="switchApprovalTab('tab-updates')">
            <span>🔄 Client Detail Updates</span>
            <span class="tab-badge">{{ $pendingClientUpdates->count() }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="btn-tab-all" onclick="switchApprovalTab('tab-all')">
            <span>👁️ View All Categories</span>
        </button>
    </div>

    <!-- TAB 1: Pending Client Loans -->
    <div class="approval-tab-pane active" id="tab-loans">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">📋 Pending Client Loan Applications ({{ $pendingLoans->count() }})</h3>
                    <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                        Superadmin authorization required before loan release and activation.
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
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary);">{{ $loan->client->user->name ?? 'Unknown' }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">📞 {{ $loan->client->user->phone_number ?? 'N/A' }}</div>
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
                                        @if($loan->client->user->valid_id_path)
                                            <button type="button" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="previewIdImage('{{ asset($loan->client->user->valid_id_path) }}', '{{ $loan->client->user->name }}')">
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
                                            <button type="button" class="btn btn-rose btn-sm" style="padding: 4px 10px;" onclick="openDeclineModal('{{ route('host.approvals.loans.decline', $loan->id) }}', 'Loan for {{ $loan->client->user->name }}')" title="Decline Loan Application">
                                                ✕
                                            </button>
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
                        Disbursement authorizations reviewed and endorsed by Releasing Officers.
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
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary);">{{ $req->user->name }}</div>
                                        <div style="font-size: 11.5px; color: var(--text-secondary);">📞 {{ $req->user->phone_number }} ({{ strtoupper($req->user->role) }})</div>
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
                        Field collector user access approvals and area assignment authorizations.
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
                                <tr>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary);">{{ $colUser->name }}</div>
                                        <span class="badge badge-primary" style="font-size: 10.5px;">New Collector</span>
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
                                            <button type="button" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 11.5px;" onclick="previewIdImage('{{ asset($colUser->valid_id_path) }}', '{{ $colUser->name }}')">
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
                        Profile changes submitted by Encoders requiring Superadmin verification.
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
                                <tr>
                                    <td><strong>{{ $update->client->user->name ?? 'N/A' }}</strong></td>
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
                        <textarea name="reason" class="form-control" rows="4" placeholder="Enter clear explanation for audit logging..." required></textarea>
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

        function switchApprovalTab(tabId) {
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
        }
    </script>
@endpush

