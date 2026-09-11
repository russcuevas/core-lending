@extends('layouts.app')

@section('title', 'Scan Client QR')
@section('page_title', 'Collector - Scan Client QR Code')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/collector.css') }}">
@endpush

@section('content')
    <div style="max-width: 540px; margin: 0 auto;">
        <div class="card" style="padding: 16px; border-radius: var(--radius-xl);">
            <div style="text-align: center; margin-bottom: 14px;">
                <h3 style="font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                    📷 Scan Client QR Code
                </h3>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin: 0;">
                    Point camera at client's ID card or mobile QR code.
                </p>
            </div>

            <!-- Enhanced High-Tech QR Scanner Card -->
            <div class="qr-scanner-card">
                <div class="scanner-viewfinder-wrapper">
                    <!-- Live Camera Render Target -->
                    <div id="qr-reader-container"></div>

                    <!-- Floating Camera Control Bar -->
                    <div class="scanner-controls-overlay">
                        <button type="button" class="scanner-ctrl-btn" onclick="toggleCameraFacing(onQrScanned)" title="Switch Front/Back Camera">
                            🔄 Flip Camera
                        </button>
                        <button type="button" class="scanner-ctrl-btn" id="scanner-torch-btn" onclick="toggleScannerTorch()" style="display: none;" title="Toggle Flashlight">
                            💡 Torch
                        </button>
                        <label class="scanner-ctrl-btn" style="margin-bottom: 0; cursor: pointer;" title="Scan QR from Gallery Photo">
                            📁 Photo
                            <input type="file" accept="image/*" style="display: none;" onchange="scanQrFromImageFile(this, onQrScanned)">
                        </label>
                    </div>

                    <!-- Target HUD Frame -->
                    <div class="scanner-target-box" id="scanner-hud-box">
                        <div class="scanner-corner-bl"></div>
                        <div class="scanner-corner-br"></div>
                        <div class="scanner-laser-line" id="scanner-laser-line"></div>
                    </div>
                </div>

                <!-- Bottom Status Indicator -->
                <div class="scanner-bottom-bar">
                    <div class="scanner-status-text" id="scanner-status-msg">
                        Align Client QR code within the box
                    </div>
                </div>
            </div>

            <!-- Fallback: Quick Camera Capture or File Picker -->
            <div style="text-align: center; margin-bottom: 18px;">
                <label class="btn btn-outline" style="width: 100%; font-size: 13px; font-weight: 600; justify-content: center; cursor: pointer;">
                    <span>📸 Take Photo or Upload QR Image</span>
                    <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="scanQrFromImageFile(this, onQrScanned)">
                </label>
            </div>

            <div style="margin: 14px 0; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
                <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    OR Choose Client Manually
                </span>
                <span style="flex: 1; height: 1px; background: var(--border-color);"></span>
            </div>

            <!-- Manual Client Selector with Search Input Filter -->
            <form action="{{ route('collector.payments.collect_form') }}" method="GET">
                <div class="form-group" style="text-align: left; margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">Filter by Name or Mobile No.</label>
                    <input type="text" id="client-search-filter" class="form-control" placeholder="🔍 Type client name or number..." oninput="filterClientDropdown(this.value)" style="margin-bottom: 8px;">
                    
                    <select name="client_id" id="client_select_dropdown" class="form-select" required size="5" style="height: auto; max-height: 180px;">
                        <option value="" disabled selected>-- Select Client to Collect Payment --</option>
                        @foreach($clients as $c)
                            @php
                                $hasPending = $c->user && $c->user->walletTransactions && $c->user->walletTransactions->whereIn('status', ['pending_releasing_review', 'pending_host_approval', 'approved_by_host'])->isNotEmpty();
                                $l = $c->currentLoan;
                                $termDays = $l ? ($l->term_days ?? 60) : 60;
                                $insDaily = $l ? (float)($l->insurance_premium_daily > 0 ? $l->insurance_premium_daily : 25.00) : 0;
                                $totIns = $insDaily * $termDays;
                                $totPayable = $l ? ($l->total_payable + $totIns) : 0;
                                $combinedBal = $l ? max(0, $totPayable - (float)$l->total_paid) : 0;
                            @endphp
                            <option value="{{ $c->id }}" {{ $hasPending ? 'disabled' : '' }} data-text="{{ strtolower($c->user->name . ' ' . $c->user->phone_number) }}">
                                {{ $c->user->name }} ({{ $c->user->phone_number }}) • Bal: ₱{{ number_format($combinedBal, 2) }} {{ $hasPending ? '• 🔒 (Pending Approval)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-emerald" style="width: 100%; padding: 10px; font-weight: 700;">
                    Proceed to Payment Collection &rarr;
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/collector.js') }}"></script>
    <script>
        function onQrScanned(qrToken) {
            window.location.href = `/collector/payments/collect?qr_token=${encodeURIComponent(qrToken)}`;
        }

        function filterClientDropdown(query) {
            const term = query.toLowerCase().trim();
            const select = document.getElementById('client_select_dropdown');
            const options = select.querySelectorAll('option:not(:first-child)');
            
            options.forEach(opt => {
                const text = opt.getAttribute('data-text') || opt.innerText.toLowerCase();
                if (!term || text.includes(term)) {
                    opt.style.display = '';
                } else {
                    opt.style.display = 'none';
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            initQrScanner(onQrScanned);
        });
    </script>
@endpush
