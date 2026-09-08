@extends('layouts.app')

@section('title', 'Clients Directory')
@section('page_title', 'Admin Encoder - Clients Directory')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <form method="GET" action="{{ route('admin.encoder.clients.index') }}" style="display: flex; gap: 8px; max-width: 400px; width: 100%; flex: 1;">
            <input type="text" name="search" class="form-control" placeholder="Search client name or phone..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-emerald">Search</button>
        </form>

        <a href="{{ route('admin.encoder.clients.create') }}" class="btn btn-emerald">
            + Encode New Client
        </a>
    </div>

    <!-- Clients Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👥 All Registered Clients</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Contact No (CP)</th>
                        <th>Assigned Collector</th>
                        <th>Principal Loan</th>
                        <th>Daily Installment</th>
                        <th>Remaining Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$clients->isEmpty())
                        @foreach($clients as $client)
                            <tr>
                                <td>
                                    <strong>{{ $client->user->name }}</strong>
                                    <div style="font-size: 11.5px; color: var(--text-secondary);">{{ $client->user->address }}</div>
                                </td>
                                <td>{{ $client->user->phone_number }}</td>
                                <td>{{ $client->collector->user->name ?? 'None' }}</td>
                                <td style="font-weight: 700;">
                                    ₱{{ number_format($client->currentLoan->principal_amount ?? 0, 2) }}
                                </td>
                                <td style="font-weight: 600; color: #d97706;">
                                    ₱{{ number_format($client->currentLoan->daily_installment ?? 0, 2) }} / day
                                </td>
                                <td style="font-weight: 700; color: #059669;">
                                    ₱{{ number_format($client->currentLoan->remaining_balance ?? 0, 2) }}
                                </td>
                                <td>
                                    @php
                                        $cLoan = $client->currentLoan;
                                        $isLoanActive = $cLoan && $cLoan->status === 'active';
                                        $isLoanPending = $cLoan && in_array($cLoan->status, ['pending_host_approval', 'approved_for_release', 'ready_for_release', 'pending_releasing_review']);
                                        $isLoanCompleted = $cLoan && $cLoan->status === 'completed';
                                    @endphp
                                    @if($isLoanActive)
                                        <span class="badge badge-emerald">Active Loan</span>
                                    @elseif($isLoanPending)
                                        <span class="badge badge-amber" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a;">⏳ Pending Host</span>
                                    @elseif($isLoanCompleted)
                                        <span class="badge badge-emerald" style="background:#d1fae5; color:#065f46; font-weight:600;">✓ Completed</span>
                                    @else
                                        <span class="badge badge-slate">{{ str_replace('_', ' ', $client->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                        @if($isLoanCompleted || !$cLoan || in_array($cLoan->status, ['rejected', 'declined']))
                                            <button type="button" class="btn btn-sm btn-emerald" style="font-weight: 700; box-shadow: 0 2px 4px rgba(5,150,105,0.2);" onclick="openRenewModal('{{ $client->id }}', '{{ addslashes($client->user->name) }}', '{{ $client->collector_id }}')" title="Renew Loan for this client">
                                                🔄 Renew Loan
                                            </button>
                                        @elseif($isLoanPending)
                                            <span class="badge badge-amber" style="font-size: 11px; padding: 4px 6px;">⏳ In Review</span>
                                        @endif

                                        <a href="{{ route('admin.encoder.print_qr', $client->id) }}" class="btn btn-sm btn-outline" target="_blank" title="Print QR & Schedule">
                                            🖨 QR Card
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openUpdateModal('{{ $client->id }}', '{{ addslashes($client->user->name) }}', '{{ $client->user->phone_number }}', '{{ addslashes($client->user->address) }}', '{{ $client->collector_id }}')">
                                            ✏ Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 20px; color: var(--text-muted);">No client records found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top: 14px;">
            {{ $clients->links() }}
        </div>
    </div>

    <!-- Renew Loan Modal (Submits New Loan Application to Host for Approval) -->
    <div class="modal-overlay" id="renewLoanModal">
        <div class="modal-box" style="max-width: 540px;">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title" id="renewModalTitle">🔄 Loan Renewal / Re-Loan</h3>
                    <div style="font-size: 12px; color: var(--text-secondary);">Client profile and ID are verified. Submit new loan cycle for Host Approval.</div>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('renewLoanModal')">&times;</button>
            </div>
            <form id="renewLoanForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: rgba(5, 150, 105, 0.08); border: 1px solid rgba(5, 150, 105, 0.2); padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div style="font-size: 12px; font-weight: 700; color: #047857; margin-bottom: 4px;">📌 Host-Enforced Loan Terms:</div>
                        <div style="display: flex; gap: 16px; font-size: 12.5px; color: var(--text-primary);">
                            <div><strong>Interest:</strong> {{ $defaultInterestRate ?? 10 }}%</div>
                            <div><strong>Term:</strong> {{ $defaultTermDays ?? 60 }} Days (Daily)</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">New Principal Loan Amount (₱) <span style="color:red;">*</span></label>
                        <input type="number" step="100" name="loan_amount" id="renew_principal" class="form-control" placeholder="Enter amount, e.g. 10000" required oninput="calculateRenewLoan()">
                        
                        <!-- Quick Preset Buttons -->
                        <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(5000)">₱5,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(10000)">₱10,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(15000)">₱15,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(20000)">₱20,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(30000)">₱30,000</button>
                            <button type="button" class="btn btn-sm btn-outline" style="padding: 2px 8px; font-size: 11px;" onclick="setRenewAmount(50000)">₱50,000</button>
                        </div>
                    </div>

                    <!-- Live Computation Preview Box -->
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">Calculation Breakdown</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Interest ({{ $defaultInterestRate ?? 10 }}%)</span>
                                <span style="font-size: 14px; font-weight: 700; color: #d97706;" id="renew_interest_preview">₱0.00</span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Payable</span>
                                <span style="font-size: 14px; font-weight: 800; color: #059669;" id="renew_payable_preview">₱0.00</span>
                            </div>
                            <div style="grid-column: span 2; border-top: 1px dashed var(--border-color); padding-top: 8px; margin-top: 4px;">
                                <span style="font-size: 12px; color: var(--text-secondary);">Daily Installment ({{ $defaultTermDays ?? 60 }} Days): </span>
                                <strong style="font-size: 16px; color: #0284c7;" id="renew_daily_preview">₱0.00 / day</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assigned Collector <span style="color:red;">*</span></label>
                        <select name="collector_id" id="renew_collector" class="form-select" required>
                            @foreach($collectors as $col)
                                <option value="{{ $col->id }}">{{ $col->user->name }} ({{ $col->assigned_area }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Notes / Renewal Reason (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Good paying client re-loan request"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('renewLoanModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">Submit to Host for Approval &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Client Details Update Modal (Submitted to Host for Approval) -->
    <div class="modal-overlay" id="updateClientModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="updateModalTitle">Request Client Profile Update</h3>
                <button type="button" class="modal-close" onclick="closeModal('updateClientModal')">&times;</button>
            </div>
            <form id="updateClientForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 12px; color: var(--amber); background: var(--amber-light); padding: 8px 12px; border-radius: 6px; margin-bottom: 14px;">
                        ⚠ Note: Any modification to client details must be reviewed and approved by Host Superadmin before taking effect.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="name" id="modal_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number (CP No)</label>
                        <input type="text" name="phone_number" id="modal_phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Residential Address</label>
                        <textarea name="address" id="modal_address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Assigned Collector</label>
                        <select name="collector_id" id="modal_collector" class="form-select" required>
                            @foreach($collectors as $col)
                                <option value="{{ $col->id }}">{{ $col->user->name }} ({{ $col->assigned_area }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Update Request</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Client changed SIM card or moved to new address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('updateClientModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Submit to Host for Approval</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const hostInterestRate = {{ (float)($defaultInterestRate ?? 10) }};
        const hostTermDays = {{ (int)($defaultTermDays ?? 60) }};

        function openRenewModal(clientId, name, collectorId) {
            document.getElementById('renewLoanForm').action = '/admin/encoder/clients/' + clientId + '/renew-loan';
            document.getElementById('renewModalTitle').innerText = '🔄 Renew Loan for ' + name;
            document.getElementById('renew_collector').value = collectorId;
            document.getElementById('renew_principal').value = '';
            calculateRenewLoan();
            openModal('renewLoanModal');
        }

        function setRenewAmount(amount) {
            document.getElementById('renew_principal').value = amount;
            calculateRenewLoan();
        }

        function calculateRenewLoan() {
            const principal = parseFloat(document.getElementById('renew_principal').value) || 0;
            const interest = principal * (hostInterestRate / 100);
            const totalPayable = principal + interest;
            const daily = hostTermDays > 0 ? (totalPayable / hostTermDays) : 0;

            document.getElementById('renew_interest_preview').innerText = '₱' + interest.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('renew_payable_preview').innerText = '₱' + totalPayable.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('renew_daily_preview').innerText = '₱' + daily.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' / day';
        }

        function openUpdateModal(clientId, name, phone, address, collectorId) {
            document.getElementById('updateClientForm').action = '/admin/encoder/clients/' + clientId + '/update-request';
            document.getElementById('updateModalTitle').innerText = 'Request Update for ' + name;
            document.getElementById('modal_name').value = name;
            document.getElementById('modal_phone').value = phone;
            document.getElementById('modal_address').value = address;
            document.getElementById('modal_collector').value = collectorId;
            openModal('updateClientModal');
        }
    </script>
@endpush
