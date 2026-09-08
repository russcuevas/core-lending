@extends('layouts.app')

@section('title', 'Manage System Credentials & Users')
@section('page_title', 'Host Superadmin - System Credentials & User Accounts')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <p style="color: var(--text-secondary); margin: 0; font-size: 13px;">Create credentials for Admins, Releasing Officers, Collectors, and manage client accounts.</p>
        <button type="button" class="btn btn-emerald" onclick="openModal('createStaffModal')">
            + Create New Staff Account
        </button>
    </div>

    <!-- Admins & Staff Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👨‍💼 System Administrators & Staff</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email / Username</th>
                        <th>Contact Number</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($admins as $admin)
                        <tr>
                            <td><strong>{{ $admin->name }}</strong></td>
                            <td>{{ $admin->email }}</td>
                            <td>{{ $admin->phone_number ?? 'N/A' }}</td>
                            <td>
                                @if($admin->role === 'host')
                                    <span class="badge badge-indigo">Host Superadmin</span>
                                @elseif($admin->role === 'admin_encoder')
                                    <span class="badge badge-emerald">Admin Encoder</span>
                                @elseif($admin->role === 'admin_releasing')
                                    <span class="badge badge-amber">Releasing Officer</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $admin->status === 'active' ? 'badge-emerald' : 'badge-rose' }}">
                                    {{ $admin->status }}
                                </span>
                            </td>
                            <td>{{ $admin->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($admin->id !== auth()->id())
                                    <form action="{{ route('host.accounts.toggle_status', $admin->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline">
                                            {{ $admin->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Collectors Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🛵 Field Collectors</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Collector Name</th>
                        <th>Email</th>
                        <th>Contact No (CP)</th>
                        <th>Assigned Area</th>
                        <th>Commission Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collectors as $col)
                        <tr>
                            <td><strong>{{ $col->user->name }}</strong></td>
                            <td>{{ $col->user->email }}</td>
                            <td>{{ $col->user->phone_number }}</td>
                            <td>{{ $col->assigned_area }}</td>
                            <td style="font-weight: 700; color: #059669;">₱{{ number_format($col->commission_balance, 2) }}</td>
                            <td>
                                <span class="badge {{ $col->user->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                    {{ $col->user->status }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline" onclick="openResetPinModal('{{ $col->user->id }}', '{{ $col->user->name }}')">
                                    Reset PIN
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Registered Clients -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👤 Registered Clients</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Contact No (Username)</th>
                        <th>Assigned Collector</th>
                        <th>Wallet Balance</th>
                        <th>Current Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $cUser)
                        <tr>
                            <td><strong>{{ $cUser->name }}</strong></td>
                            <td>{{ $cUser->phone_number }}</td>
                            <td>{{ $cUser->client->collector->user->name ?? 'None' }}</td>
                            <td style="font-weight: 700;">₱{{ number_format($cUser->client->wallet_balance ?? 0, 2) }}</td>
                            <td>
                                <span class="badge {{ $cUser->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                    {{ $cUser->status }}
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline" onclick="openResetPinModal('{{ $cUser->id }}', '{{ $cUser->name }}')">
                                    Reset PIN
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 14px;">
            {{ $clients->links() }}
        </div>
    </div>

    <!-- Create Staff Modal -->
    <div class="modal-overlay" id="createStaffModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Create New Staff Account</h3>
                <button type="button" class="modal-close" onclick="closeModal('createStaffModal')">&times;</button>
            </div>
            <form action="{{ route('host.accounts.store_staff') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Account Role</label>
                        <select name="role" class="form-select" required onchange="toggleAreaInput(this.value)">
                            <option value="admin_encoder">Admin Encoder</option>
                            <option value="admin_releasing">Admin Releasing Officer</option>
                            <option value="collector">Field Collector</option>
                            <option value="host">Host Superadmin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Maria Clara" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address (Login Username)</label>
                        <input type="email" name="email" class="form-control" placeholder="staff@lending.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number (CP No)</label>
                        <input type="text" name="phone_number" class="form-control" placeholder="09181234567">
                    </div>

                    <div class="form-group" id="assignedAreaGroup" style="display: none;">
                        <label class="form-label">Assigned Collection Area</label>
                        <input type="text" name="assigned_area" class="form-control" placeholder="e.g. District 1 - Pasig">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Initial Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Office / Residential Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Address details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('createStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset PIN Modal -->
    <div class="modal-overlay" id="resetPinModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title" id="resetPinTitle">Reset PIN Code</h3>
                <button type="button" class="modal-close" onclick="closeModal('resetPinModal')">&times;</button>
            </div>
            <form id="resetPinForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">New 4-Digit PIN Code</label>
                        <input type="password" name="pin_code" class="form-control" maxlength="4" placeholder="1234" required style="letter-spacing: 4px; font-size: 18px; text-align: center;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('resetPinModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Save New PIN</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleAreaInput(role) {
            const group = document.getElementById('assignedAreaGroup');
            if (role === 'collector') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        }

        function openResetPinModal(userId, userName) {
            document.getElementById('resetPinForm').action = '/host/accounts/' + userId + '/reset-pin';
            document.getElementById('resetPinTitle').innerText = 'Reset PIN for ' + userName;
            openModal('resetPinModal');
        }
    </script>
@endpush
