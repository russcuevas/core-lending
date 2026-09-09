@extends('layouts.app')

@section('title', 'Admin Encoder Dashboard')
@section('page_title', 'Admin Encoder - Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
@endpush

@section('content')
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon emerald">📝</div>
            <div class="stat-info">
                <div class="stat-label">Total Clients Encoded</div>
                <div class="stat-value">{{ $totalClientsEncoded }}</div>
                <div class="stat-subtext">Registered in database</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">⏳</div>
            <div class="stat-info">
                <div class="stat-label">Pending Host Approvals</div>
                <div class="stat-value">{{ $pendingHostApprovals }}</div>
                <div class="stat-subtext">Awaiting Superadmin sign-off</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo">🛵</div>
            <div class="stat-info">
                <div class="stat-label">Active Field Collectors</div>
                <div class="stat-value">{{ $collectors->count() }}</div>
                <div class="stat-subtext">Assigned across areas</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">🧾</div>
            <div class="stat-info">
                <div class="stat-label">Today's Recorded Expenses</div>
                <div class="stat-value">₱{{ number_format($expensesToday, 2) }}</div>
                <div class="stat-subtext">Operational receipts</div>
            </div>
        </div>
    </div>

    <!-- Quick Action Bar -->
    <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
        <a href="{{ route('admin.encoder.clients.create') }}" class="btn btn-emerald">
            + Encode New Client
        </a>
        <a href="{{ route('admin.encoder.expenses.index') }}" class="btn btn-outline">
            + Record Expense
        </a>
        <a href="{{ route('admin.encoder.collectors.create') }}" class="btn btn-outline">
            + Register Collector
        </a>
        <a href="{{ route('admin.encoder.daily_payments') }}" class="btn btn-outline">
            🖨 Print Daily Payments Sheet
        </a>
    </div>

    <!-- Recent Encoded Clients -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recently Encoded Clients</h3>
            <a href="{{ route('admin.encoder.clients.index') }}" class="btn btn-sm btn-outline">View All Clients &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Date Encoded</th>
                        <th>Client Name</th>
                        <th>Contact No</th>
                        <th>Loan Amount</th>
                        <th>Assigned Collector</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$recentClients->isEmpty())
                        @foreach($recentClients as $c)
                            <tr>
                                <td>{{ $c->created_at->format('M d, Y') }}</td>
                                <td><strong>{{ $c->user->name ?? 'N/A' }}</strong></td>
                                <td>{{ $c->user->phone_number ?? 'N/A' }}</td>
                                <td style="font-weight: 700; color: #059669;">
                                    ₱{{ number_format($c->currentLoan->principal_amount ?? 0, 2) }}
                                </td>
                                <td>{{ $c->collector->user->name ?? 'None' }}</td>
                                <td>
                                    <span class="badge {{ $c->status === 'active' ? 'badge-emerald' : 'badge-amber' }}">
                                        {{ str_replace('_', ' ', $c->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.encoder.print_qr', $c->id) }}" class="btn btn-sm btn-outline" target="_blank">
                                        🖨 QR Card
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 20px; color: var(--text-muted);">No clients encoded yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
