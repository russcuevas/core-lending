@extends('layouts.app')

@section('title', 'Daily Client Payments Sheet')
@section('page_title', 'Daily Client Payments Paper Sheet')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;" class="no-print">
        <form method="GET" action="{{ route('admin.encoder.daily_payments') }}" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Payment Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <button type="submit" class="btn btn-emerald">Load Payments</button>
        </form>

        <button type="button" class="btn btn-emerald" onclick="window.print()">
            🖨 Print Paper Records
        </button>
    </div>

    <!-- Paper Formatted Records per Client -->
    <div style="max-width: 900px; margin: 0 auto;">
        <div class="print-header" style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0077b6; padding-bottom: 12px;">
            <img src="{{ asset('images/logo.jpg') }}" alt="Core Lending Logo" style="height: 44px; object-fit: contain; margin-bottom: 4px; border-radius: 8px;">
            <h2 style="margin: 0; font-size: 20px; text-transform: uppercase; color: #091e3a;">CORE <span style="color: #0077b6;">LENDING</span></h2>
            <h4 style="margin: 3px 0; font-size: 14px; color: var(--text-secondary);">Daily Client Payments Summary Sheet & Verification Proofs</h4>
            <div style="font-size: 12.5px; font-weight: 600;">Date: {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</div>
        </div>

        @if(!$payments->isEmpty())
            @foreach($payments as $payment)
                <div class="card" style="page-break-inside: avoid; border: 1.5px solid #0f172a; margin-bottom: 16px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h3 style="font-size: 16px; margin-bottom: 2px;">{{ $payment->client->user->name ?? 'Unknown Client' }}</h3>
                            <div style="font-size: 12px; color: var(--text-secondary);">
                                Contact No: {{ $payment->client->user->phone_number ?? 'N/A' }} | Address: {{ $payment->client->user->address ?? 'N/A' }}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge badge-emerald" style="font-size: 11px; padding: 3px 8px;">Official Receipt #PAY-{{ $payment->id }}</span>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">{{ $payment->created_at->format('h:i A') }}</div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; align-items: center;">
                        <!-- Payment Details Table -->
                        <div>
                            @php
                                $pRemBal = $payment->client_remaining_balance_after;
                                $pRemLoan = 0;
                                $pRemIns = 0;
                                if ($payment->loan) {
                                    $pL = $payment->loan;
                                    $pTermDays = $pL->schedules ? ($pL->schedules->count() > 0 ? $pL->schedules->count() : ($pL->term_days ?? 60)) : 60;
                                    $pInsDaily = (float)($pL->insurance_premium_daily > 0 ? $pL->insurance_premium_daily : 25.00);
                                    $pTotIns = $pInsDaily * $pTermDays;
                                    $pPaidDays = $pL->days_paid_count;
                                    $pRemIns = max(0, $pTotIns - ($pPaidDays * $pInsDaily));
                                    $pRemLoan = (float)$pL->remaining_balance;
                                    $pRemBal = max(0, $pRemLoan + $pRemIns);
                                }
                            @endphp
                            <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 5px 0; color: var(--text-secondary);">Collector Assigned:</td>
                                    <td style="padding: 5px 0; font-weight: 600;">{{ $payment->collector->user->name ?? 'Direct' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: var(--text-secondary);">Amount Paid:</td>
                                    <td style="padding: 5px 0; font-weight: 700; color: #059669; font-size: 15px;">
                                        ₱{{ number_format($payment->amount_paid, 2) }}
                                        <span style="font-size: 11px; font-weight: normal; color: var(--text-secondary); margin-left: 6px;">
                                            (₱{{ number_format($payment->loan_premium_amount > 0 ? $payment->loan_premium_amount : ($payment->amount_paid - ($payment->insurance_premium_amount ?? 25)), 2) }} Loan + ₱{{ number_format($payment->insurance_premium_amount ?? 25, 2) }} Ins)
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: var(--text-secondary);">Remaining Balance:</td>
                                    <td style="padding: 5px 0; font-weight: 700; color: #059669; font-size: 14.5px;">
                                        ₱{{ number_format($pRemBal, 2) }}
                                        @if($payment->loan)
                                            <span style="font-size: 11px; font-weight: normal; color: var(--text-secondary); margin-left: 6px;">
                                                (₱{{ number_format($pRemLoan, 2) }} Loan + ₱{{ number_format($pRemIns, 2) }} Ins)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: var(--text-secondary);">PIN Verification:</td>
                                    <td style="padding: 5px 0;"><span style="color: #059669; font-weight: 600;">✓ Client PIN Verified</span></td>
                                </tr>
                                <tr>
                                    <td style="padding: 5px 0; color: var(--text-secondary);">Notes:</td>
                                    <td style="padding: 5px 0;">{{ $payment->notes ?? 'Daily collection' }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Proof Picture & Download Button -->
                        <div style="text-align: center; background: #f8fafc; padding: 10px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                            <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; color: var(--text-secondary);">
                                Camera Proof Photo
                            </div>

                            @if($payment->proof_image_path)
                                <img src="{{ asset($payment->proof_image_path) }}" alt="Proof Photo" style="width: 100%; max-height: 130px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; margin-bottom: 6px;">
                                <a href="{{ asset($payment->proof_image_path) }}" download="Proof_Pay_{{ $payment->id }}_{{ $payment->client->user->name }}.jpg" class="btn btn-sm btn-outline no-print" style="width: 100%; font-size: 11px;">
                                    ⬇ Download Proof Image
                                </a>
                            @else
                                <div style="padding: 24px 10px; color: var(--text-muted); font-size: 11.5px;">
                                    No camera photo uploaded.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div style="margin-top: 12px; padding-top: 8px; border-top: 1px dashed var(--border-color); display: flex; justify-content: space-between; font-size: 11px; color: var(--text-secondary); flex-wrap: wrap; gap: 6px;">
                        <span>Collector Signature: _________________________</span>
                        <span>Client Signature: _________________________</span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="card" style="text-align: center; padding: 30px; color: var(--text-secondary);">
                No client payments recorded on {{ $date }}.
            </div>
        @endif
    </div>
@endsection
