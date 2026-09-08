@extends('layouts.app')

@section('title', 'Financial Reports & Statistics')
@section('page_title', 'Host Superadmin - Financial Reports & Statistics')

@section('content')
    <!-- Date Filter -->
    <div class="card no-print">
        <form method="GET" action="{{ route('host.reports.index') }}" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Report Period From</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Report Period To</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <button type="submit" class="btn btn-emerald">Generate Report</button>
            <button type="button" class="btn btn-outline" onclick="window.print()">🖨 Print Report</button>
        </form>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon emerald">₱</div>
            <div class="stat-info">
                <div class="stat-label">Total Cash In Inflow</div>
                <div class="stat-value">₱{{ number_format($cashInTotal, 2) }}</div>
                <div class="stat-subtext">{{ $startDate }} to {{ $endDate }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">₱</div>
            <div class="stat-info">
                <div class="stat-label">Total Cash Out Released</div>
                <div class="stat-value">₱{{ number_format($cashOutTotal, 2) }}</div>
                <div class="stat-subtext">{{ $startDate }} to {{ $endDate }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo">₱</div>
            <div class="stat-info">
                <div class="stat-label">Total Loan Collections</div>
                <div class="stat-value">₱{{ number_format($loanCollectionsTotal, 2) }}</div>
                <div class="stat-subtext">{{ $startDate }} to {{ $endDate }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald">₱</div>
            <div class="stat-info">
                <div class="stat-label">Total Savings Deposited</div>
                <div class="stat-value">₱{{ number_format($savingsTotal, 2) }}</div>
                <div class="stat-subtext">Locked in 60-day funds</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">₱</div>
            <div class="stat-info">
                <div class="stat-label">Total Operating Expenses</div>
                <div class="stat-value">₱{{ number_format($expensesTotal, 2) }}</div>
                <div class="stat-subtext">{{ $startDate }} to {{ $endDate }}</div>
            </div>
        </div>
    </div>

    <!-- Daily Payment Breakdown -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daily Client Payment Ledger ({{ $startDate }} to {{ $endDate }})</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client Name</th>
                        <th>Collector</th>
                        <th>Amount Paid</th>
                        <th>Loan Balance Remaining</th>
                        <th>PIN Verified</th>
                        <th>Proof Photo</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$payments->isEmpty())
                        @foreach($payments as $p)
                            <tr>
                                <td>{{ $p->payment_date }}</td>
                                <td><strong>{{ $p->client->user->name ?? 'N/A' }}</strong></td>
                                <td>{{ $p->collector->user->name ?? 'None' }}</td>
                                <td style="font-weight: 700; color: #059669;">₱{{ number_format($p->amount_paid, 2) }}</td>
                                <td style="font-weight: 600;">₱{{ number_format($p->client_remaining_balance_after, 2) }}</td>
                                <td><span class="badge badge-emerald">✓ Verified</span></td>
                                <td>
                                    @if($p->proof_image_path)
                                        <a href="{{ asset($p->proof_image_path) }}" target="_blank" class="btn btn-sm btn-outline">
                                            View Proof
                                        </a>
                                    @else
                                        <span style="color: var(--text-muted); font-size:12px;">No Photo</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 20px; color: var(--text-muted);">No payments found in this period.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top: 14px;">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
