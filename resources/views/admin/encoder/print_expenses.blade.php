<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses Report - Core Lending</title>
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
            font-size: 13px;
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

        .filter-form input, .filter-form select {
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
            font-size: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 6px;
        }

        table.plain-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        table.plain-table th, table.plain-table td {
            border: 1px solid #000000;
            padding: 6px 8px;
            text-align: left;
        }

        table.plain-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 11px;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .total-row td {
            font-weight: bold;
            font-size: 13px;
            background-color: #f9f9f9;
        }

        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            page-break-inside: avoid;
        }

        .signature-block {
            width: 220px;
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #000000;
            margin-top: 50px;
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
            <form method="GET" action="{{ route('admin.encoder.expenses.print') }}" class="filter-form">
                <label>From:</label>
                <input type="date" name="start_date" value="{{ $startDate }}">
                
                <label>To:</label>
                <input type="date" name="end_date" value="{{ $endDate }}">

                <label>Category:</label>
                <select name="category">
                    <option value="">All Categories</option>
                    <option value="Office Supplies" {{ $selectedCategory == 'Office Supplies' ? 'selected' : '' }}>Office Supplies</option>
                    <option value="Transportation / Fuel" {{ $selectedCategory == 'Transportation / Fuel' ? 'selected' : '' }}>Transportation / Fuel</option>
                    <option value="Utilities" {{ $selectedCategory == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                    <option value="Representation" {{ $selectedCategory == 'Representation' ? 'selected' : '' }}>Representation</option>
                    <option value="Miscellaneous" {{ $selectedCategory == 'Miscellaneous' ? 'selected' : '' }}>Miscellaneous</option>
                </select>

                <button type="submit" class="btn">Filter</button>
                <a href="{{ route('admin.encoder.expenses.print') }}" class="btn">Reset</a>
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
            <h2>OPERATIONAL EXPENSES SUMMARY REPORT</h2>
            <div style="font-size: 11px; color: #333;">Official Documentation of Corporate Expenditures</div>
        </div>

        <!-- Meta Information -->
        <div class="meta-info">
            <div>
                <strong>Date Range:</strong> 
                @if($startDate && $endDate)
                    {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @elseif($startDate)
                    From {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }}
                @elseif($endDate)
                    Until {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                @else
                    All Recorded Expenses (All Time)
                @endif
                @if($selectedCategory)
                    | <strong>Category:</strong> {{ $selectedCategory }}
                @endif
            </div>
            <div>
                <strong>Printed Date:</strong> {{ \Carbon\Carbon::now()->format('F d, Y h:i A') }} | 
                <strong>Printed By:</strong> {{ auth()->user()->name ?? 'Admin Encoder' }}
            </div>
        </div>

        <!-- Expenses Table -->
        <table class="plain-table">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="width: 95px;">Date</th>
                    <th>Particulars / Description</th>
                    <th style="width: 150px;">Category</th>
                    <th style="width: 110px;" class="text-right">Amount (₱)</th>
                    <th style="width: 130px;">Recorded By</th>
                    <th style="width: 90px;" class="text-center">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $index => $exp)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($exp->date)->format('Y-m-d') }}</td>
                        <td><strong>{{ $exp->particulars }}</strong></td>
                        <td>{{ $exp->category }}</td>
                        <td class="text-right">₱{{ number_format($exp->amount, 2) }}</td>
                        <td>{{ $exp->user->name ?? 'Admin' }}</td>
                        <td class="text-center">
                            {{ $exp->receipt_image_path ? 'With Receipt' : 'None' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px;">
                            No expenses found for the selected period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right">GRAND TOTAL EXPENSES:</td>
                    <td class="text-right">₱{{ number_format($totalAmount, 2) }}</td>
                    <td colspan="2">({{ $expenses->count() }} Records)</td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Section -->
        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">
                    {{ auth()->user()->name ?? 'Admin Encoder' }}
                </div>
                <div>Prepared By (Encoder)</div>
            </div>

            <div class="signature-block">
                <div class="signature-line">
                    Finance / Releasing Officer
                </div>
                <div>Verified By</div>
            </div>

            <div class="signature-block">
                <div class="signature-line">
                    Host Superadmin / Management
                </div>
                <div>Approved By</div>
            </div>
        </div>
    </div>

</body>
</html>
