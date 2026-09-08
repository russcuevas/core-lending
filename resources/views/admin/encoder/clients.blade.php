@extends('layouts.app')

@section('title', 'Clients Directory')
@section('page_title', 'Admin Encoder - Clients Directory')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <form method="GET" action="{{ route('admin.encoder.clients.index') }}" style="display: flex; gap: 10px; max-width: 400px; width: 100%;">
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
            <table class="data-table">
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
                    @forelse($clients as $client)
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
                                <span class="badge {{ $client->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                    {{ str_replace('_', ' ', $client->status) }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="{{ route('admin.encoder.print_qr', $client->id) }}" class="btn btn-sm btn-outline" target="_blank" title="Print QR & Schedule">
                                        🖨 QR Card
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline" onclick="openUpdateModal('{{ $client->id }}', '{{ addslashes($client->user->name) }}', '{{ $client->user->phone_number }}', '{{ addslashes($client->user->address) }}', '{{ $client->collector_id }}')">
                                        ✏ Edit (Request Host)
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 24px; color: var(--text-muted);">No client records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">
            {{ $clients->links() }}
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
                    <p style="font-size: 12.5px; color: var(--amber); background: var(--amber-light); padding: 8px 12px; border-radius: 6px; margin-bottom: 16px;">
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
