@extends('layouts.app')

@section('title', 'Master Approvals Hub')
@section('page_title', 'Host Superadmin - Master Approvals Hub')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <!-- SECTION 1: Pending Client Loans -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>📋</span> Pending Client Loan Applications ({{ $pendingLoans->count() }})
            </h3>
            <span class="badge badge-amber">Must be approved before tagging active</span>
        </div>

        @if($pendingLoans->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No pending loan applications waiting for approval.
            </div>
        @else
            <div class="host-approval-grid">
                @foreach($pendingLoans as $loan)
                    <div class="approval-card loan">
                        <div>
                            <div class="approval-header">
                                <div>
                                    <h4 style="font-size: 15px; margin-bottom: 2px;">{{ $loan->client->user->name ?? 'Unknown' }}</h4>
                                    <div style="font-size: 12px; color: var(--text-secondary);">CP: {{ $loan->client->user->phone_number ?? 'N/A' }}</div>
                                </div>
                                <span class="approval-tag loan">Loan App</span>
                            </div>

                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Principal Amount</span>
                                <span class="approval-meta-val" style="color:#059669; font-size:15px;">₱{{ number_format($loan->principal_amount, 2) }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Interest Rate</span>
                                <span class="approval-meta-val">{{ $loan->interest_rate_percent }}% (60 Days Term)</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Total Payable</span>
                                <span class="approval-meta-val">₱{{ number_format($loan->total_payable, 2) }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Daily Installment</span>
                                <span class="approval-meta-val">₱{{ number_format($loan->daily_installment, 2) }} / day</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Assigned Collector</span>
                                <span class="approval-meta-val">{{ $loan->collector->user->name ?? 'None' }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Encoded By</span>
                                <span class="approval-meta-val">{{ $loan->encoder->name ?? 'Encoder' }}</span>
                            </div>

                            @if($loan->client->user->valid_id_path)
                                <div style="margin-top: 10px;">
                                    <a href="{{ asset($loan->client->user->valid_id_path) }}" target="_blank" class="btn btn-sm btn-outline" style="width: 100%;">
                                        🔍 View Client Valid ID
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="approval-actions">
                            <form action="{{ route('host.approvals.loans.approve', $loan->id) }}" method="POST" style="flex: 1;">
                                @csrf
                                <button type="submit" class="btn btn-emerald btn-sm" style="width: 100%;">✓ Approve Loan</button>
                            </form>

                            <button type="button" class="btn btn-rose btn-sm" onclick="openDeclineModal('{{ route('host.approvals.loans.decline', $loan->id) }}', 'Loan for {{ $loan->client->user->name }}')">
                                ✕ Decline
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECTION 2: Pending Cash In & Cash Out Requests -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>💸</span> Cash-In & Cash-Out Transaction Approvals ({{ $pendingWalletRequests->count() }})
            </h3>
            <span class="badge badge-indigo">Releasing Officer Reviewed</span>
        </div>

        @if($pendingWalletRequests->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No pending cash-in or cash-out requests.
            </div>
        @else
            <div class="host-approval-grid">
                @foreach($pendingWalletRequests as $req)
                    <div class="approval-card {{ $req->type === 'cash_in' ? 'cashin' : 'cashout' }}">
                        <div>
                            <div class="approval-header">
                                <div>
                                    <h4 style="font-size: 15px; margin-bottom: 2px;">{{ $req->user->name }}</h4>
                                    <div style="font-size: 12px; color: var(--text-secondary);">CP: {{ $req->user->phone_number }} ({{ $req->user->role }})</div>
                                </div>
                                <span class="approval-tag {{ $req->type === 'cash_in' ? 'cashin' : 'cashout' }}">
                                    {{ str_replace('_', ' ', $req->type) }}
                                </span>
                            </div>

                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Request Amount</span>
                                <span class="approval-meta-val" style="color: {{ $req->type === 'cash_in' ? '#059669' : '#d97706' }}; font-size:16px;">
                                    ₱{{ number_format($req->amount, 2) }}
                                </span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Reviewed By Releasing</span>
                                <span class="approval-meta-val">{{ $req->releasingOfficer->name ?? 'Releasing Officer' }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Releasing Notes</span>
                                <span class="approval-meta-val">{{ $req->releasing_notes ?? 'None' }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Target Release Date</span>
                                <span class="approval-meta-val">{{ $req->releasing_scheduled_date ?? 'Immediate' }}</span>
                            </div>
                        </div>

                        <div class="approval-actions">
                            <form action="{{ route('host.approvals.wallet.approve', $req->id) }}" method="POST" style="flex: 1;">
                                @csrf
                                <button type="submit" class="btn btn-emerald btn-sm" style="width: 100%;">✓ Authorize Release</button>
                            </form>

                            <button type="button" class="btn btn-rose btn-sm" onclick="openDeclineModal('{{ route('host.approvals.wallet.decline', $req->id) }}', 'Request for {{ $req->user->name }}')">
                                ✕ Decline
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECTION 3: Pending Collector Registrations -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>🛵</span> Collector Account Registrations ({{ $pendingCollectors->count() }})
            </h3>
        </div>

        @if($pendingCollectors->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No pending collector accounts.
            </div>
        @else
            <div class="host-approval-grid">
                @foreach($pendingCollectors as $colUser)
                    <div class="approval-card collector">
                        <div>
                            <div class="approval-header">
                                <div>
                                    <h4 style="font-size: 15px;">{{ $colUser->name }}</h4>
                                    <div style="font-size: 12px; color: var(--text-secondary);">CP: {{ $colUser->phone_number }} | {{ $colUser->email }}</div>
                                </div>
                                <span class="approval-tag collector">New Collector</span>
                            </div>

                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Assigned Area</span>
                                <span class="approval-meta-val">{{ $colUser->collector->assigned_area ?? 'General' }}</span>
                            </div>
                            <div class="approval-meta-row">
                                <span class="approval-meta-label">Residential Address</span>
                                <span class="approval-meta-val">{{ $colUser->address ?? 'N/A' }}</span>
                            </div>

                            @if($colUser->valid_id_path)
                                <div style="margin-top: 10px;">
                                    <a href="{{ asset($colUser->valid_id_path) }}" target="_blank" class="btn btn-sm btn-outline" style="width: 100%;">
                                        🔍 View Collector Valid ID
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="approval-actions">
                            <form action="{{ route('host.approvals.collectors.approve', $colUser->id) }}" method="POST" style="flex: 1;">
                                @csrf
                                <button type="submit" class="btn btn-emerald btn-sm" style="width: 100%;">✓ Approve Account</button>
                            </form>

                            <form action="{{ route('host.approvals.collectors.decline', $colUser->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-rose btn-sm">✕ Decline</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- SECTION 4: Client Detail Update Requests -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>🔄</span> Client Details Update Requests ({{ $pendingClientUpdates->count() }})
            </h3>
        </div>

        @if($pendingClientUpdates->isEmpty())
            <div style="padding: 24px; text-align: center; color: var(--text-secondary);">
                ✓ No client profile change requests.
            </div>
        @else
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Requested By</th>
                            <th>Old Information</th>
                            <th>New Requested Information</th>
                            <th>Reason / Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingClientUpdates as $update)
                            <tr>
                                <td><strong>{{ $update->client->user->name ?? 'N/A' }}</strong></td>
                                <td>{{ $update->requester->name ?? 'Admin' }}</td>
                                <td>
                                    <div style="font-size:12px;">Name: {{ $update->old_data['name'] ?? '' }}</div>
                                    <div style="font-size:12px;">Phone: {{ $update->old_data['phone_number'] ?? '' }}</div>
                                    <div style="font-size:12px;">Address: {{ $update->old_data['address'] ?? '' }}</div>
                                </td>
                                <td>
                                    <div style="font-size:12px; color:#059669; font-weight:600;">Name: {{ $update->new_data['name'] ?? '' }}</div>
                                    <div style="font-size:12px; color:#059669; font-weight:600;">Phone: {{ $update->new_data['phone_number'] ?? '' }}</div>
                                    <div style="font-size:12px; color:#059669; font-weight:600;">Address: {{ $update->new_data['address'] ?? '' }}</div>
                                </td>
                                <td>{{ $update->notes ?? 'N/A' }}</td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <form action="{{ route('host.approvals.client_updates.approve', $update->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-emerald">Approve</button>
                                        </form>
                                        <form action="{{ route('host.approvals.client_updates.decline', $update->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-rose">Decline</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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
                        <label class="form-label">State Reason for Declining</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Enter clear explanation..." required></textarea>
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
    </script>
@endpush
