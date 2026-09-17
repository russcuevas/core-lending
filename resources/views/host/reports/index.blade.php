@extends('layouts.app')

@section('title', 'Financial Report')
@section('page_title', 'Host Superadmin - Financial Report')

@section('content')
    <!-- Date Filter & Actions -->
    <div class="card no-print" style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 14px;">
            <form method="GET" action="{{ route('host.reports.index') }}" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Report Period From</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Report Period To</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <button type="submit" class="btn btn-emerald" style="font-weight: 700;">Generate Report</button>
            </form>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" class="btn" onclick="toggleProfitLossCard()" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; font-weight: 700; border: none; box-shadow: 0 2px 6px rgba(5,150,105,0.3); display: inline-flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border-radius: 6px;">
                    <span>📊</span> Profit & Loss Statement
                </button>
                <a href="{{ route('host.reports.print', ['start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="btn btn-outline" style="font-weight: 700; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;">
                    <span>🖨</span> Print Clean Report
                </a>
            </div>
        </div>
    </div>

    <!-- Profit & Loss Interactive Executive Section -->
    <div id="profitLossSection" class="card" style="display: block; margin-bottom: 24px; border: 2px solid #059669; background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #bbf7d0; padding-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: #059669; color: #fff; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px;">📊</div>
                <div>
                    <h3 class="card-title" style="margin: 0; color: #065f46; font-size: 18px; font-weight: 800;">Executive Profit & Loss Statement</h3>
                    <div style="font-size: 12px; color: #047857; font-weight: 600;">Formula: Profit & Loss = Total Income - Total Expense</div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #065f46;">Net Profit / (Loss)</div>
                <div style="font-size: 24px; font-weight: 900; color: {{ $profitAndLoss >= 0 ? '#047857' : '#e11d48' }};">
                    {{ $profitAndLoss >= 0 ? '+' : '' }}₱{{ number_format($profitAndLoss, 2) }}
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; padding: 16px 0;">
            <!-- Income Column -->
            <div style="background: #ffffff; border: 1px solid #86efac; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 2px solid #22c55e; padding-bottom: 8px;">
                    <div style="font-weight: 800; font-size: 15px; color: #166534; display: flex; align-items: center; gap: 6px;">
                        <span>📥</span> Total Income
                    </div>
                    <div style="font-weight: 900; font-size: 18px; color: #166534;">
                        ₱{{ number_format($totalIncome, 2) }}
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                        <span style="color: var(--text-main); font-weight: 600;">Actual Money Vault:</span>
                        <strong style="color: #047857;">₱{{ number_format($actualMoneyVault, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                        <span style="color: var(--text-main); font-weight: 600;">Loan Premium Balance (All Clients):</span>
                        <strong style="color: #1e40af;">₱{{ number_format($loanPremiumBalanceAll, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                        <span style="color: var(--text-main); font-weight: 600;">Insurance Premium Balance:</span>
                        <strong style="color: #0284c7;">₱{{ number_format($insurancePremiumBalanceAll, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Expense Column -->
            <div style="background: #ffffff; border: 1px solid #fca5a5; border-radius: 10px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 2px solid #ef4444; padding-bottom: 8px;">
                    <div style="font-weight: 800; font-size: 15px; color: #991b1b; display: flex; align-items: center; gap: 6px;">
                        <span>📤</span> Total Expense
                    </div>
                    <div style="font-weight: 900; font-size: 18px; color: #991b1b;">
                        ₱{{ number_format($totalExpense, 2) }}
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                        <span style="color: var(--text-main); font-weight: 600;">Office Expenses (Operating):</span>
                        <strong style="color: #b91c1c;">₱{{ number_format($allTimeExpensesTotal, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0;">
                        <span style="color: var(--text-main); font-weight: 600;">Savings Interest (All Clients):</span>
                        <strong style="color: #c2410c;">₱{{ number_format($allClientsSavingsInterest, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .reports-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        @media (max-width: 1200px) {
            .reports-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .reports-stats-grid {
                grid-template-columns: 1fr;
            }
        }
        .reports-stats-grid .stat-label {
            white-space: normal;
            line-height: 1.25;
            min-height: 28px;
        }
    </style>

    <!-- Summary Statistics Grid -->
    <div class="stats-grid reports-stats-grid">
        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-icon emerald">₱</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Cash In Inflow">Total Cash In Inflow</div>
                <div class="stat-value">₱{{ number_format($cashInTotal, 2) }}</div>
                <div class="stat-subtext">{{ $startDate }} to {{ $endDate }}</div>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-icon amber">₱</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Cash Out Released">Total Cash Out Released</div>
                <div class="stat-value">₱{{ number_format($cashOutTotal, 2) }}</div>
                <div class="stat-subtext">₱{{ number_format($loanReleasesTotal ?? $cashOutTotal, 2) }} Loans + ₱{{ number_format($walletCashOutTotal ?? 0, 2) }} Cash Out</div>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #6366f1;">
            <div class="stat-icon indigo">₱</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Loan Collections">Total Loan Collections</div>
                <div class="stat-value">₱{{ number_format($loanCollectionsTotal, 2) }}</div>
                <div class="stat-subtext">₱{{ number_format($loanPrincipalTotal, 2) }} Principal + ₱{{ number_format($insuranceTotal, 2) }} Ins.</div>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #0284c7;">
            <div class="stat-icon emerald" style="background: rgba(2, 132, 199, 0.12); color: #0284c7;">🛡️</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Insurance Premium">Total Insurance Premium</div>
                <div class="stat-value" style="color: #0284c7;">₱{{ number_format($insuranceTotal, 2) }}</div>
                <div class="stat-subtext">Reserves collected in period</div>
            </div>
        </div>

        <!-- Total Vault Savings -->
        <div class="stat-card" style="border-left: 4px solid #059669;">
            <div class="stat-icon emerald">🏦</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Vault Savings">Total Vault Savings</div>
                <div class="stat-value" style="color: #059669;">₱{{ number_format($totalVaultSavings, 2) }}</div>
                <div class="stat-subtext">Locked in savings capital fund</div>
            </div>
        </div>

        <!-- Total Client Wallet (Floating in client portals) -->
        <div class="stat-card" style="border-left: 4px solid #8b5cf6; background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; font-size: 20px;">👛</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Client Wallet">Total Client Wallet</div>
                <div class="stat-value" style="color: #7c3aed;">₱{{ number_format($totalClientWallet, 2) }}</div>
                <div class="stat-subtext">Floating client portal balance (All)</div>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #10b981;">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #059669; font-size: 20px;">📈</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Savings Interest">Total Savings Interest</div>
                <div class="stat-value" style="color: #059669;">₱{{ number_format($savingsInterestTotal, 2) }}</div>
                <div class="stat-subtext">₱{{ number_format($savingsExpectedInterestTotal, 2) }} expected yield</div>
            </div>
        </div>

        <div class="stat-card" style="border-left: 4px solid #e11d48;">
            <div class="stat-icon rose">₱</div>
            <div class="stat-info">
                <div class="stat-label" title="Total Operating Expenses">Total Operating Expenses</div>
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
                                        $pPaidDays = $pL->schedules ? $pL->schedules->where('status', 'paid')->count() : 0;
                                        $pRemIns = max(0, $pTotIns - ($pPaidDays * $pInsDaily));
                                        $pRemBal = max(0, (float)$pL->remaining_balance + $pRemIns);
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

    <script>
        function toggleProfitLossCard() {
            const el = document.getElementById('profitLossSection');
            if (el) {
                if (el.style.display === 'none') {
                    el.style.display = 'block';
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }
    </script>
@endsection
