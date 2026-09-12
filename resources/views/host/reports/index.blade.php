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
            <a href="{{ route('host.reports.print', ['start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="btn btn-outline" style="font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <span>🖨</span> Print Clean Report
            </a>
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
                <div class="stat-subtext">₱{{ number_format($loanPrincipalTotal, 2) }} Principal + ₱{{ number_format($insuranceTotal, 2) }} Ins.</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">🛡️</div>
            <div class="stat-info">
                <div class="stat-label">Total Insurance Premium</div>
                <div class="stat-value" style="color: #0284c7;">₱{{ number_format($insuranceTotal, 2) }}</div>
                <div class="stat-subtext">Reserves collected in period</div>
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
                        <th>Loan Premium</th>
                        <th>Insurance Premium</th>
                        <th>Total Amount Paid</th>
                        <th>Loan Balance Remaining</th>
                        <th>PIN Verified</th>
                        <th>Proof Photo</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$payments->isEmpty())
                        @foreach($payments as $p)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('M d, Y') }}</td>
                                <td><strong>{{ $p->client->user->name ?? 'N/A' }}</strong></td>
                                <td>
                                    <span class="badge badge-slate">{{ $p->collector->user->name ?? 'None' }}</span>
                                </td>
                                <td style="font-weight: 600; color: #1e40af;">
                                    ₱{{ number_format($p->loan_premium_amount > 0 ? $p->loan_premium_amount : ($p->amount_paid - ($p->insurance_premium_amount ?? 5)), 2) }}
                                </td>
                                <td style="font-weight: 600; color: #0284c7;">
                                    ₱{{ number_format($p->insurance_premium_amount ?? 5.00, 2) }}
                                </td>
                                <td style="font-weight: 700; color: #059669; font-size: 14.5px;">
                                    ₱{{ number_format($p->amount_paid, 2) }}
                                </td>
                                @php
                                    $pRemBal = $p->client_remaining_balance_after;
                                    if ($p->loan) {
                                        $pL = $p->loan;
                                        $pTermDays = $pL->schedules ? ($pL->schedules->count() > 0 ? $pL->schedules->count() : ($pL->term_days ?? 60)) : 60;
                                        $pInsDaily = (float)($pL->insurance_premium_daily > 0 ? $pL->insurance_premium_daily : 25.00);
                                        $pTotIns = $pInsDaily * $pTermDays;
                                        $pTotPayable = $pL->total_payable + $pTotIns;
                                        $pRemBal = max(0, $pTotPayable - (float)$pL->total_paid);
                                    }
                                @endphp
                                <td style="font-weight: 700; color: #059669;">
                                    ₱{{ number_format($pRemBal, 2) }}
                                    <div style="font-size: 10px; color: var(--text-muted); font-weight: normal;">(Principal+Int+Ins)</div>
                                </td>
                                <td>
                                    @if($p->client_pin_verified)
                                        <span class="badge badge-emerald">✓ Verified (PIN)</span>
                                    @else
                                        <span class="badge badge-slate">Direct</span>
                                    @endif
                                </td>
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
                            <td colspan="9" class="text-center" style="padding: 20px; color: var(--text-muted);">No payments found in this period.</td>
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
