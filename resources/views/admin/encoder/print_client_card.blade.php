@extends('layouts.app')

@section('title', 'Print Client QR & Schedule Card')
@section('page_title', 'Print Client Account Card & 60-Day Loan Schedule')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;" class="no-print">
        <a href="{{ route('admin.encoder.clients.index') }}" class="btn btn-outline">&larr; Back to Clients</a>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-emerald" onclick="window.print()">🖨 Print QR Card for Releasing Officer</button>
        </div>
    </div>

    <!-- Printable Client Card Section -->
    <div class="print-card-container">
        <div class="print-header" style="text-align: center;">
            <img src="{{ asset('images/logo.jpg') }}" alt="Core Lending Logo" style="height: 44px; object-fit: contain; margin-bottom: 4px; border-radius: 8px;">
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 3px; text-transform: uppercase; color: var(--brand-navy);">CORE <span style="color: var(--brand-blue);">LENDING</span></h2>
            <p style="font-size: 12px; color: var(--text-secondary); margin: 0;">Official Client Account & 60-Day Loan Schedule Card • Funding Your Future</p>
        </div>

        <div class="print-qr-section">
            <div style="text-align: left;">
                <h3 style="font-size: 17px; margin-bottom: 4px;">{{ $client->user->name }}</h3>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 3px;">
                    <strong>Contact No:</strong> {{ $client->user->phone_number }}
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 3px;">
                    <strong>Address:</strong> {{ $client->user->address }}
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 3px;">
                    <strong>Assigned Collector:</strong> {{ $client->collector->user->name ?? 'None' }} ({{ $client->collector->assigned_area ?? 'General' }})
                </div>
                <div style="font-size: 12.5px; color: var(--text-secondary);">
                    <strong>Account Status:</strong> <span class="badge badge-emerald">{{ $client->status }}</span>
                </div>
            </div>

            <!-- Client QR Code Image -->
            <div style="text-align: center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($client->qr_code_token) }}" alt="QR Code" class="print-qr-img">
                <div style="font-size: 11px; font-family: monospace; font-weight: 700; margin-top: 4px;">
                    {{ $client->qr_code_token }}
                </div>
            </div>
        </div>

        <!-- Loan Summary Box -->
        @if($client->currentLoan)
            @php $loan = $client->currentLoan; @endphp
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; background: #f1f5f9; padding: 12px; border-radius: var(--radius-sm); margin-bottom: 16px; font-size: 12.5px;">
                <div>
                    <span style="color: var(--text-secondary); display:block; font-size:11px;">Principal Amount</span>
                    <strong>₱{{ number_format($loan->principal_amount, 2) }}</strong>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display:block; font-size:11px;">Interest ({{ $loan->interest_rate_percent }}%)</span>
                    <strong>₱{{ number_format($loan->total_payable - $loan->principal_amount, 2) }}</strong>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display:block; font-size:11px;">Total Payable</span>
                    <strong style="color: #059669;">₱{{ number_format($loan->total_payable, 2) }}</strong>
                </div>
                <div>
                    <span style="color: var(--text-secondary); display:block; font-size:11px;">Daily Installment</span>
                    <strong style="color: #d97706;">₱{{ number_format($loan->daily_installment, 2) }} / day</strong>
                </div>
            </div>

            <!-- 60-Day Payment Schedule Table -->
            <h4 style="font-size: 14px; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 3px;">
                60-Day Loan Repayment Schedule Table
            </h4>

            <div class="table-responsive">
                <table class="data-table" style="font-size: 11px; min-width: 500px;">
                    <thead>
                        <tr>
                            <th style="padding: 5px 8px;">Day</th>
                            <th style="padding: 5px 8px;">Due Date</th>
                            <th style="padding: 5px 8px;">Expected Amount</th>
                            <th style="padding: 5px 8px;">Paid Amount</th>
                            <th style="padding: 5px 8px;">Status</th>
                            <th style="padding: 5px 8px;">Collector Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loan->schedules as $sch)
                            @php
                                $isFullySettled = ($loan->remaining_balance <= 0) || ($loan->status === 'fully_paid') || ($client->status === 'completed');
                                $isLastDay = ($sch->day_number == $loan->term_days);
                                $isSchedulePaid = ($sch->status === 'paid') || ($isFullySettled && ($sch->paid_amount > 0 || $sch->status !== 'unpaid' || $isLastDay));
                            @endphp
                            <tr>
                                <td style="padding: 4px 8px; font-weight: 600;">Day {{ $sch->day_number }}</td>
                                <td style="padding: 4px 8px;">{{ $sch->due_date }}</td>
                                <td style="padding: 4px 8px;">₱{{ number_format($sch->expected_amount, 2) }}</td>
                                <td style="padding: 4px 8px;">
                                    @if($sch->paid_amount > 0)
                                        ₱{{ number_format($sch->paid_amount, 2) }}
                                    @elseif($isSchedulePaid)
                                        ₱{{ number_format($sch->expected_amount, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="padding: 4px 8px;">
                                    <span class="badge {{ $isSchedulePaid ? 'badge-emerald' : ($sch->status === 'partial' ? 'badge-amber' : 'badge-slate') }}" style="font-size: 9.5px;">
                                        {{ $isSchedulePaid ? 'paid' : $sch->status }}
                                    </span>
                                </td>
                                <td style="padding: 4px 8px; border-bottom: 1px dotted #ccc; width: 140px;"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div style="margin-top: 24px; display: flex; justify-content: space-between; font-size: 11.5px; padding-top: 16px; border-top: 1px solid #000; flex-wrap: wrap; gap: 12px;">
            <div>
                <div>_______________________________</div>
                <div style="font-weight: 600; margin-top: 3px;">Prepared by Admin Encoder</div>
            </div>
            <div>
                <div>_______________________________</div>
                <div style="font-weight: 600; margin-top: 3px;">Acknowledged by Releasing Officer</div>
            </div>
            <div>
                <div>_______________________________</div>
                <div style="font-weight: 600; margin-top: 3px;">Client Signature</div>
            </div>
        </div>
    </div>
@endsection
