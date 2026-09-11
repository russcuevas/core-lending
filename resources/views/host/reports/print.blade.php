<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report ({{ $startDate }} to {{ $endDate }}) - Core Lending</title>
    <style>
        /* Plain, Clean Black & White Paper Print Styles */
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #000000;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .no-print {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-size: 12px;
            font-weight: bold;
        }

        .filter-form input {
            padding: 5px 8px;
            font-size: 12px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
        }

        .btn {
            padding: 6px 14px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            border: 1px solid #334155;
            background: #ffffff;
            color: #0f172a;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }

        .print-container {
            max-width: 960px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            margin: 0 0 6px 0;
            font-size: 14px;
            font-weight: normal;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            margin-bottom: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 6px;
        }

        .section-heading {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 18px 0 8px 0;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }

        table.plain-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11px;
        }

        table.plain-table th, table.plain-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            text-align: left;
        }

        table.plain-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10.5px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11.5px;
            background-color: #f9f9f9;
        }

        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 11.5px;
            page-break-inside: avoid;
        }

        .signature-block {
            width: 220px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000000;
            margin-top: 45px;
            padding-top: 4px;
            font-weight: bold;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                margin: 0;
            }

            .print-container {
                width: 100%;
                max-width: 100%;
            }

            table.plain-table th {
                background-color: #e5e5e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <div class="print-container">
        <!-- Control bar (hidden during print) -->
        <div class="no-print">
            <form method="GET" action="{{ route('host.reports.print') }}" class="filter-form">
                <label>Period From:</label>
                <input type="date" name="start_date" value="{{ $startDate }}" required>
                
                <label>Period To:</label>
                <input type="date" name="end_date" value="{{ $endDate }}" required>

                <button type="submit" class="btn">Filter & Generate</button>
                <a href="{{ route('host.reports.print') }}" class="btn">Reset to Month</a>
            </form>

            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    🖨 Print / Save as PDF
                </button>
                <button type="button" class="btn" onclick="window.close()">
                    ✕ Close
                </button>
            </div>
        </div>

        <!-- Document Header -->
        <div class="header">
            <h1>CORE LENDING CORPORATION</h1>
            <h2>EXECUTIVE FINANCIAL STATEMENT & AUDIT REPORT</h2>
            <div style="font-size: 11px; color: #333;">Comprehensive Financial Operations & Micro-Insurance Summary</div>
        </div>

        <!-- Meta Information -->
        <div class="meta-info">
            <div>
                <strong>Audit Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
            </div>
            <div>
                <strong>Generated On:</strong> {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }} | 
                <strong>Authorized By:</strong> {{ auth()->user()->name ?? 'Host Superadmin' }}
            </div>
        </div>

        <!-- 1. Financial Key Performance Indicators Summary -->
        <div class="section-heading">1. Financial Inflow & Outflow Summary ({{ $startDate }} to {{ $endDate }})</div>
        <table class="plain-table">
            <thead>
                <tr>
                    <th>Account / Metric</th>
                    <th>Classification</th>
                    <th class="text-right">Total Amount (₱)</th>
                    <th>Audit Remarks</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Total Cash-In Inflows</strong></td>
                    <td>Inflow (+)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($cashInTotal, 2) }}</td>
                    <td>Processed client & collector office deposits</td>
                </tr>
                <tr>
                    <td><strong>Total Loan Repayments Collected</strong></td>
                    <td>Inflow (+)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($loanCollectionsTotal, 2) }}</td>
                    <td>Principal: ₱{{ number_format($loanPrincipalTotal, 2) }} | Insurance: ₱{{ number_format($insuranceTotal, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Micro-Insurance Premiums Collected</strong></td>
                    <td>Reserve Inflow (+)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($insuranceTotal, 2) }}</td>
                    <td>Guaranteed pool for borrower protections</td>
                </tr>
                <tr>
                    <td><strong>Total Client Savings Deposited</strong></td>
                    <td>Deposit Inflow (+)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($savingsTotal, 2) }}</td>
                    <td>60-Day term locked-in savings capital</td>
                </tr>
                <tr>
                    <td><strong>Total Cash-Out / Disbursements Released</strong></td>
                    <td>Outflow (-)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($cashOutTotal, 2) }}</td>
                    <td>Disbursed loans, client withdrawals & commissions</td>
                </tr>
                <tr>
                    <td><strong>Total Operating Expenses</strong></td>
                    <td>Outflow (-)</td>
                    <td class="text-right" style="font-weight: bold;">₱{{ number_format($expensesTotal, 2) }}</td>
                    <td>Office operations, supplies & utility disbursements</td>
                </tr>
            </tbody>
            <tfoot>
                @php
                    $netOperatingCash = ($cashInTotal + $loanCollectionsTotal + $savingsTotal) - ($cashOutTotal + $expensesTotal);
                @endphp
                <tr class="total-row">
                    <td colspan="2" class="text-right">NET CASH MOVEMENT IN PERIOD:</td>
                    <td class="text-right">{{ $netOperatingCash >= 0 ? '+' : '' }}₱{{ number_format($netOperatingCash, 2) }}</td>
                    <td>{{ $netOperatingCash >= 0 ? 'Positive Operating Cash Flow' : 'Net Disbursement Period' }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- 2. Detailed Daily Loan Payment Collections -->
        <div class="section-heading">2. Loan Collections Ledger Breakdown ({{ $payments->count() }} Payments)</div>
        <table class="plain-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th style="width: 80px;">Date</th>
                    <th>Client Name</th>
                    <th>Collector</th>
                    <th class="text-right" style="width: 85px;">Loan Amort.</th>
                    <th class="text-right" style="width: 80px;">Insurance</th>
                    <th class="text-right" style="width: 95px;">Amount Paid</th>
                    <th class="text-right" style="width: 90px;">Loan Balance</th>
                    <th style="width: 65px;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $idx => $p)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d') }}</td>
                        <td><strong>{{ $p->client->user->name ?? 'Client' }}</strong></td>
                        <td>{{ $p->collector->user->name ?? 'Direct' }}</td>
                        <td class="text-right">₱{{ number_format($p->loan_premium_amount > 0 ? $p->loan_premium_amount : ($p->amount_paid - ($p->insurance_premium_amount ?? 0)), 2) }}</td>
                        <td class="text-right">₱{{ number_format($p->insurance_premium_amount ?? 0, 2) }}</td>
                        <td class="text-right" style="font-weight: bold;">₱{{ number_format($p->amount_paid, 2) }}</td>
                        <td class="text-right">₱{{ number_format($p->client_remaining_balance_after ?? 0, 2) }}</td>
                        <td class="text-center">{{ strtoupper($p->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 16px;">No collection payments found within this date range.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right">TOTAL COLLECTIONS:</td>
                    <td class="text-right">₱{{ number_format($loanPrincipalTotal, 2) }}</td>
                    <td class="text-right">₱{{ number_format($insuranceTotal, 2) }}</td>
                    <td class="text-right">₱{{ number_format($loanCollectionsTotal, 2) }}</td>
                    <td colspan="2">({{ $payments->count() }} Payments)</td>
                </tr>
            </tfoot>
        </table>

        <!-- 3. Operational Expenses Breakdown -->
        @if($expenses->isNotEmpty())
            <div class="section-heading">3. Operating Expenses Breakdown ({{ $expenses->count() }} Records)</div>
            <table class="plain-table">
                <thead>
                    <tr>
                        <th style="width: 30px;" class="text-center">#</th>
                        <th style="width: 80px;">Date</th>
                        <th>Particulars / Description</th>
                        <th>Category</th>
                        <th class="text-right" style="width: 100px;">Amount (₱)</th>
                        <th style="width: 140px;">Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $eIdx => $exp)
                        <tr>
                            <td class="text-center">{{ $eIdx + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($exp->date)->format('Y-m-d') }}</td>
                            <td><strong>{{ $exp->particulars }}</strong></td>
                            <td>{{ $exp->category }}</td>
                            <td class="text-right" style="font-weight: bold;">₱{{ number_format($exp->amount, 2) }}</td>
                            <td>{{ $exp->user->name ?? 'Admin' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" class="text-right">TOTAL OPERATING EXPENSES:</td>
                        <td class="text-right">₱{{ number_format($expensesTotal, 2) }}</td>
                        <td>({{ $expenses->count() }} Records)</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <!-- Signatures Section -->
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">
                    Finance / Releasing Officer
                </div>
                <div>Prepared & Audited By</div>
            </div>

            <div class="signature-block">
                <div class="signature-line">
                    {{ auth()->user()->name ?? 'Host Superadmin' }}
                </div>
                <div>Reviewed & Verified By</div>
            </div>

            <div class="signature-block">
                <div class="signature-line">
                    Executive Board / Management
                </div>
                <div>Approved & Received By</div>
            </div>
        </div>
    </div>

</body>
</html>
