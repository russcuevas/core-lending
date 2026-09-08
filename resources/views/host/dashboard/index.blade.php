@extends('layouts.app')

@section('title', 'Host Superadmin Dashboard')
@section('page_title', 'Host Superadmin Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <!-- Host Vault Hero Card -->
    <div class="vault-hero-card">
        <div>
            <div class="vault-title">Official Host Vault Balance</div>
            <div class="vault-amount">₱{{ number_format($vaultBalance, 2) }}</div>
            <div style="font-size: 13.5px; color: #ffffff; opacity: 0.9; margin-top: 6px; font-weight: 500;">Real-time available corporate funds</div>
        </div>
        <div class="vault-stats-row">
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Total Active Loans</div>
                <div class="vault-stat-subval">₱{{ number_format($totalActiveLoans, 2) }}</div>
            </div>
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Total Active Savings</div>
                <div class="vault-stat-subval">₱{{ number_format($totalSavingsActive, 2) }}</div>
            </div>
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Active Borrowers</div>
                <div class="vault-stat-subval">{{ $totalActiveClientsCount }} Clients</div>
            </div>
        </div>
    </div>

    <!-- Daily Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon emerald">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Cash In</div>
                <div class="stat-value">₱{{ number_format($todayCashIn, 2) }}</div>
                <div class="stat-subtext">Received into host vault</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Cash Out</div>
                <div class="stat-value">₱{{ number_format($todayCashOut, 2) }}</div>
                <div class="stat-subtext">Disbursed from vault</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Loan Collections</div>
                <div class="stat-value">₱{{ number_format($todayLoanCollections, 2) }}</div>
                <div class="stat-subtext">From daily field payments</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Expenses</div>
                <div class="stat-value">₱{{ number_format($todayExpenses, 2) }}</div>
                <div class="stat-subtext">Operating expenditures</div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Alert Banner -->
    @if($totalPendingApprovals > 0)
        <div style="background: var(--amber-light); border-left: 4px solid var(--amber); padding: 16px 20px; border-radius: var(--radius-md); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 24px;">⚠</span>
                <div>
                    <strong style="color: #92400e; font-size: 14px;">{{ $totalPendingApprovals }} Pending Items Requiring Host Approval</strong>
                    <div style="font-size: 12.5px; color: #b45309;">
                        Loans ({{ $pendingLoanCount }}), Cash Requests ({{ $pendingWalletCount }}), Collectors ({{ $pendingCollectorCount }}), Updates ({{ $pendingClientUpdatesCount }})
                    </div>
                </div>
            </div>
            <a href="{{ route('host.approvals.index') }}" class="btn btn-sm btn-amber">Review All Approvals &rarr;</a>
        </div>
    @endif

    <!-- Weekly Chart & Recent Ledger Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Weekly Cash Movement Trend</h3>
            </div>
            <div style="height: 300px; position: relative;">
                <canvas id="dailyTransactionChart"></canvas>
            </div>
        </div>

        <!-- Quick Summary & Access -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Operations</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="{{ route('host.approvals.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Master Approvals Hub</span>
                    <span class="badge badge-amber">{{ $totalPendingApprovals }}</span>
                </a>
                <a href="{{ route('host.accounts.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Create User Credentials</span>
                    <span>+</span>
                </a>
                <a href="{{ route('host.transactions.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Host Vault Audit Trail</span>
                    <span>&rarr;</span>
                </a>
                <a href="{{ route('host.reports.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Financial Reports</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Vault Ledger Entries -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Host Vault Transactions</h3>
            <a href="{{ route('host.transactions.index') }}" class="btn btn-sm btn-outline">View All &rarr;</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Vault Balance After</th>
                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($tx->type === 'in')
                                    <span class="badge badge-emerald">Cash In (Inflow)</span>
                                @else
                                    <span class="badge badge-amber">Cash Out (Outflow)</span>
                                @endif
                            </td>
                            <td><span style="font-weight: 600; text-transform: uppercase; font-size: 11.5px;">{{ str_replace('_', ' ', $tx->category) }}</span></td>
                            <td style="font-weight: 700; color: {{ $tx->type === 'in' ? '#059669' : '#d97706' }};">
                                {{ $tx->type === 'in' ? '+' : '-' }}₱{{ number_format($tx->amount, 2) }}
                            </td>
                            <td>{{ $tx->description }}</td>
                            <td style="font-weight: 700;">₱{{ number_format($tx->vault_balance_after, 2) }}</td>
                            <td>{{ $tx->creator->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 24px; color: var(--text-muted);">No transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/host.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initHostCharts({
                labels: {!! json_encode($chartLabels) !!},
                cashIn: {!! json_encode($chartCashIn) !!},
                cashOut: {!! json_encode($chartCashOut) !!},
                collections: {!! json_encode($chartCollections) !!}
            });
        });
    </script>
@endpush
