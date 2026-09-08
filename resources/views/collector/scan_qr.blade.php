@extends('layouts.app')

@section('title', 'Scan Client QR')
@section('page_title', 'Collector - Scan Client QR Code')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/collector.css') }}">
@endpush

@section('content')
    <div style="max-width: 600px; margin: 0 auto;">
        <div class="card" style="text-align: center;">
            <div class="card-header" style="justify-content: center;">
                <h3 class="card-title">📷 Scan Client QR Code for Daily Collection</h3>
            </div>

            <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 16px;">
                Align the client's printed or mobile QR code within the camera frame below.
            </p>

            <!-- QR Reader Container -->
            <div class="qr-scanner-card">
                <div id="reader"></div>
            </div>

            <div style="margin: 16px 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
                <span style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">OR Select Client Manually</span>
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
            </div>

            <!-- Manual Selector (if client has no phone or QR) -->
            <form action="{{ route('collector.payments.collect_form') }}" method="GET">
                <div class="form-group" style="text-align: left;">
                    <label class="form-label">Client Name / Contact Number</label>
                    <select name="client_id" class="form-select" required>
                        <option value="">-- Choose Client to Process Payment --</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->user->name }} ({{ $c->user->phone_number }}) - Loan Balance: ₱{{ number_format($c->currentLoan->remaining_balance ?? 0, 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-emerald" style="width: 100%;">
                    Proceed to Payment Collection &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/collector.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initQrScanner((qrToken) => {
                window.location.href = `/collector/payments/collect?qr_token=${encodeURIComponent(qrToken)}`;
            });
        });
    </script>
@endpush
