@extends('layouts.app')

@section('title', 'Manage System Credentials & Users')
@section('page_title', 'Host Superadmin - System Credentials & User Accounts')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <p style="color: var(--text-secondary); margin: 0; font-size: 13px;">Manage role-based system credentials, collector commissions, and client security PINs.</p>
        <button type="button" class="btn btn-emerald" onclick="openModal('createStaffModal')">
            + Create New Staff Account
        </button>
    </div>

    <!-- Role-based Tabs Navigation -->
    <div class="approval-tabs-nav">
        <button type="button" class="approval-tab-btn active" id="tabBtn-staff" onclick="switchAccountTab('staff', this)">
            <span>👨‍💼</span>
            <span>Staff & Admins</span>
            <span class="tab-badge">{{ count($admins) }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="tabBtn-collectors" onclick="switchAccountTab('collectors', this)">
            <span>🛵</span>
            <span>Field Collectors</span>
            <span class="tab-badge">{{ count($collectors) }}</span>
        </button>
        <button type="button" class="approval-tab-btn" id="tabBtn-clients" onclick="switchAccountTab('clients', this)">
            <span>👤</span>
            <span>Borrower Clients</span>
            <span class="tab-badge">{{ $clients->total() }}</span>
        </button>
    </div>

    <!-- TAB 1: Admins & Staff -->
    <div class="approval-tab-pane active" id="tab-staff">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 class="card-title">👨‍💼 System Administrators & Executive Staff</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 2px 0 0 0;">Users with administrative portal access permissions</p>
                </div>
                <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('createStaffModal')">
                    + Add Staff
                </button>
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
                            <th style="text-align: right;">Actions</th>
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
                                        {{ ucfirst($admin->status) }}
                                    </span>
                                </td>
                                <td>{{ $admin->created_at->format('M d, Y') }}</td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openResetPinModal('{{ $admin->id }}', '{{ $admin->name }}', 'staff')">
                                            Reset PIN
                                        </button>
                                        @if($admin->id !== auth()->id())
                                            <form action="{{ route('host.accounts.toggle_status', $admin->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="tab" value="staff">
                                                <button type="submit" class="btn btn-sm {{ $admin->status === 'active' ? 'btn-outline' : 'btn-emerald' }}">
                                                    {{ $admin->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: Collectors -->
    <div class="approval-tab-pane" id="tab-collectors">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 class="card-title">🛵 Field Collectors</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 2px 0 0 0;">Assigned collection agents, areas, and earned commissions</p>
                </div>
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
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collectors as $col)
                            <tr>
                                <td><strong>{{ $col->user->name }}</strong></td>
                                <td>{{ $col->user->email }}</td>
                                <td>{{ $col->user->phone_number }}</td>
                                <td><span class="badge badge-outline" style="font-weight: 600;">{{ $col->assigned_area ?? 'General Area' }}</span></td>
                                <td style="font-weight: 700; color: #059669;">₱{{ number_format($col->commission_balance, 2) }}</td>
                                <td>
                                    <span class="badge {{ $col->user->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                        {{ ucfirst($col->user->status) }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openResetPinModal('{{ $col->user->id }}', '{{ $col->user->name }}', 'collectors')">
                                            Reset PIN
                                        </button>
                                        <form action="{{ route('host.accounts.toggle_status', $col->user->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="tab" value="collectors">
                                            <button type="submit" class="btn btn-sm {{ $col->user->status === 'active' ? 'btn-outline' : 'btn-emerald' }}">
                                                {{ $col->user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: Registered Clients -->
    <div class="approval-tab-pane" id="tab-clients">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 class="card-title">👤 Registered Clients</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 2px 0 0 0;">Borrowers, active wallet balances, and collector assignments</p>
                </div>
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
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clients as $cUser)
                            <tr>
                                <td><strong>{{ $cUser->name }}</strong></td>
                                <td>{{ $cUser->phone_number }}</td>
                                <td>{{ $cUser->client->collector->user->name ?? 'Unassigned' }}</td>
                                <td style="font-weight: 700; color: #059669;">₱{{ number_format($cUser->client->wallet_balance ?? 0, 2) }}</td>
                                <td>
                                    <span class="badge {{ $cUser->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                        {{ ucfirst($cUser->status) }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <button type="button" class="btn btn-sm btn-outline" onclick="openResetPinModal('{{ $cUser->id }}', '{{ $cUser->name }}', 'clients')">
                                            Reset PIN
                                        </button>
                                        <form action="{{ route('host.accounts.toggle_status', $cUser->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="tab" value="clients">
                                            <button type="submit" class="btn btn-sm {{ $cUser->status === 'active' ? 'btn-outline' : 'btn-emerald' }}">
                                                {{ $cUser->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 14px;">
                {{ $clients->fragment('clients')->links() }}
            </div>
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
                <input type="hidden" name="tab" id="reset_pin_tab_input" value="clients">
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
        function switchAccountTab(tabName, btn) {
            document.querySelectorAll('.approval-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.approval-tab-pane').forEach(p => p.classList.remove('active'));
            
            if (btn) {
                btn.classList.add('active');
            } else {
                const targetBtn = document.getElementById('tabBtn-' + tabName);
                if (targetBtn) targetBtn.classList.add('active');
            }

            const pane = document.getElementById('tab-' + tabName);
            if (pane) pane.classList.add('active');

            if (history.pushState) {
                history.pushState(null, null, '#' + tabName);
            }
        }

        function checkHashAndSwitch() {
            const hash = window.location.hash.replace('#', '');
            if (hash && ['staff', 'collectors', 'clients'].includes(hash)) {
                switchAccountTab(hash, null);
            }
        }

        document.addEventListener('DOMContentLoaded', checkHashAndSwitch);
        window.addEventListener('hashchange', checkHashAndSwitch);

        function toggleAreaInput(role) {
            const group = document.getElementById('assignedAreaGroup');
            if (role === 'collector') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        }

        function openResetPinModal(userId, userName, tab = 'clients') {
            document.getElementById('resetPinForm').action = '/host/accounts/' + userId + '/reset-pin';
            document.getElementById('resetPinTitle').innerText = 'Reset PIN for ' + userName;
            document.getElementById('reset_pin_tab_input').value = tab;
            openModal('resetPinModal');
        }
    </script>
@endpush
