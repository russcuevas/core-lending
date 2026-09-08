@extends('layouts.app')

@section('title', 'Daily Client Payments Sheet')
@section('page_title', 'Daily Client Payments Paper Sheet')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;" class="no-print">
        <form method="GET" action="{{ route('admin.encoder.daily_payments') }}" style="display: flex; gap: 12px; align-items: flex-end;">
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
        <div class="print-header" style="text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0077b6; padding-bottom: 12px;">
            <img src="{{ asset('images/logo.jpg') }}" alt="Core Lending Logo" style="height: 48px; object-fit: contain; margin-bottom: 6px;">
            <h2 style="margin: 0; font-size: 22px; text-transform: uppercase; color: #091e3a;">CORE <span style="color: #0077b6;">LENDING</span></h2>
            <h4 style="margin: 4px 0; font-size: 15px; color: var(--text-secondary);">Daily Client Payments Summary Sheet & Verification Proofs</h4>
            <div style="font-size: 13px; font-weight: 600;">Date: {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</div>
        </div>

        @forelse($payments as $payment)
            <div class="card" style="page-break-inside: avoid; border: 1.5px solid #0f172a; margin-bottom: 20px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 14px;">
                    <div>
                        <h3 style="font-size: 17px; margin-bottom: 2px;">{{ $payment->client->user->name ?? 'Unknown Client' }}</h3>
                        <div style="font-size: 12.5px; color: var(--text-secondary);">
                            Contact No: {{ $payment->client->user->phone_number ?? 'N/A' }} | Address: {{ $payment->client->user->address ?? 'N/A' }}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span class="badge badge-emerald" style="font-size: 12px; padding: 4px 10px;">Official Receipt #PAY-{{ $payment->id }}</span>
                        <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">{{ $payment->created_at->format('h:i A') }}</div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: center;">
                    <!-- Payment Details Table -->
                    <div>
                        <table style="width: 100%; font-size: 13px; border-collapse: collapse;">
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-secondary);">Collector Assigned:</td>
                                <td style="padding: 6px 0; font-weight: 600;">{{ $payment->collector->user->name ?? 'Direct' }}</td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-secondary);">Amount Paid:</td>
                                <td style="padding: 6px 0; font-weight: 700; color: #059669; font-size: 16px;">
                                    ₱{{ number_format($payment->amount_paid, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-secondary);">Loan Remaining Balance:</td>
                                <td style="padding: 6px 0; font-weight: 700;">
                                    ₱{{ number_format($payment->client_remaining_balance_after, 2) }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-secondary);">PIN Verification:</td>
                                <td style="padding: 6px 0;"><span style="color: #059669; font-weight: 600;">✓ Client PIN Verified</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-secondary);">Notes:</td>
                                <td style="padding: 6px 0;">{{ $payment->notes ?? 'Daily collection' }}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Proof Picture & Download Button -->
                    <div style="text-align: center; background: #f8fafc; padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; color: var(--text-secondary);">
                            Camera Proof Photo
                        </div>

                        @if($payment->proof_image_path)
                            <img src="{{ asset($payment->proof_image_path) }}" alt="Proof Photo" style="width: 100%; max-height: 140px; object-fit: cover; border-radius: 4px; border: 1px solid #cbd5e1; margin-bottom: 8px;">
                            <a href="{{ asset($payment->proof_image_path) }}" download="Proof_Pay_{{ $payment->id }}_{{ $payment->client->user->name }}.jpg" class="btn btn-sm btn-outline no-print" style="width: 100%; font-size: 11px;">
                                ⬇ Download Proof Image
                            </a>
                        @else
                            <div style="padding: 30px 10px; color: var(--text-muted); font-size: 12px;">
                                No camera photo uploaded.
                            </div>
                        @endif
                    </div>
                </div>

                <div style="margin-top: 14px; padding-top: 10px; border-top: 1px dashed var(--border-color); display: flex; justify-content: space-between; font-size: 11.5px; color: var(--text-secondary);">
                    <span>Collector Signature: _________________________</span>
                    <span>Client Signature: _________________________</span>
                </div>
            </div>
        @empty
            <div class="card" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                No client payments recorded on {{ $date }}.
            </div>
        @endforelse
    </div>
@endsection
