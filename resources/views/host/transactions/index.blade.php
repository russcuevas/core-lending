@extends('layouts.app')

@section('title', 'Host Vault & Ledgers')
@section('page_title', 'Host Superadmin - Host Vault & System Ledgers')

@section('content')
    <!-- Filter Card -->
    <div class="card">
        <form method="GET" action="{{ route('host.transactions.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap: 16px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Flow Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types (In & Out)</option>
                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Cash In (Inflow)</option>
                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Cash Out (Outflow)</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="cash_in" {{ request('category') == 'cash_in' ? 'selected' : '' }}>Cash In</option>
                    <option value="cash_out" {{ request('category') == 'cash_out' ? 'selected' : '' }}>Cash Out</option>
                    <option value="loan_release" {{ request('category') == 'loan_release' ? 'selected' : '' }}>Loan Disbursement</option>
                    <option value="loan_repayment" {{ request('category') == 'loan_repayment' ? 'selected' : '' }}>Loan Repayment</option>
                    <option value="savings_deposit" {{ request('category') == 'savings_deposit' ? 'selected' : '' }}>Savings Deposit</option>
                    <option value="expense" {{ request('category') == 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">From Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">To Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-emerald">Filter</button>
                <a href="{{ route('host.transactions.index') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Ledgers Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📖 Comprehensive Audit Trail & Vault History</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Flow</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description / Reference</th>
                        <th>Vault Balance After</th>
                        <th>Officer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $l)
                        <tr>
                            <td>#{{ $l->id }}</td>
                            <td>{{ $l->created_at->format('Y-m-d h:i A') }}</td>
                            <td>
                                @if($l->type === 'in')
                                    <span class="badge badge-emerald">Cash In (+)</span>
                                @else
                                    <span class="badge badge-amber">Cash Out (-)</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-weight:600; text-transform:uppercase; font-size:11.5px;">
                                    {{ str_replace('_', ' ', $l->category) }}
                                </span>
                            </td>
                            <td style="font-weight:700; color: {{ $l->type === 'in' ? '#059669' : '#d97706' }};">
                                {{ $l->type === 'in' ? '+' : '-' }}₱{{ number_format($l->amount, 2) }}
                            </td>
                            <td>{{ $l->description ?? 'N/A' }}</td>
                            <td style="font-weight:700;">₱{{ number_format($l->vault_balance_after, 2) }}</td>
                            <td>{{ $l->creator->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 24px; color: var(--text-muted);">No records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">
            {{ $ledgers->links() }}
        </div>
    </div>
@endsection
