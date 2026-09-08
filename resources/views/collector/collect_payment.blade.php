@extends('layouts.app')

@section('title', 'Process Client Payment')
@section('page_title', 'Collector - Process Client Daily Payment')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/collector.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Client & Loan Info Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">{{ $client->user->name }}</h3>
                    <div style="font-size: 13px; color: var(--text-secondary);">
                        CP No: {{ $client->user->phone_number }} | Address: {{ $client->user->address }}
                    </div>
                </div>
                <span class="badge badge-emerald">Active Loan #{{ $loan->id }}</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; background: #f8fafc; padding: 16px; border-radius: var(--radius-md); margin-bottom: 20px;">
                <div>
                    <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Daily Installment Due</span>
                    <span style="font-size: 18px; font-weight: 700; color: #d97706;" id="daily_installment_due" data-due="{{ $loan->daily_installment }}">
                        ₱{{ number_format($loan->daily_installment, 2) }}
                    </span>
                </div>

                <div>
                    <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Remaining Loan Balance</span>
                    <span style="font-size: 18px; font-weight: 700; color: #059669;" id="client_remaining_balance" data-balance="{{ $loan->remaining_balance }}">
                        ₱{{ number_format($loan->remaining_balance, 2) }}
                    </span>
                </div>

                <div>
                    <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Payment Progress</span>
                    <span style="font-size: 18px; font-weight: 700;">
                        {{ $paidDays }} / 60 Days
                    </span>
                </div>

                <div>
                    <span style="font-size: 11.5px; color: var(--text-secondary); display: block;">Total Paid So Far</span>
                    <span style="font-size: 18px; font-weight: 700; color: #4f46e5;">
                        ₱{{ number_format($loan->total_paid, 2) }}
                    </span>
                </div>
            </div>

            <!-- Payment Form -->
            <form action="{{ route('collector.payments.process', $loan->id) }}" method="POST">
                @csrf
                <input type="hidden" name="photo_proof" id="collectorProofInput">

                <!-- Customizable Payment Amount Entry -->
                <div class="form-group">
                    <label class="form-label" for="payment_amount" style="font-size: 14px; font-weight: 700;">
                        Enter Payment Amount Received (₱) *
                    </label>
                    <input type="number" step="0.01" min="1" max="{{ $loan->remaining_balance }}" name="amount_paid" id="payment_amount" class="form-control" value="{{ $loan->daily_installment }}" required oninput="onPaymentAmountChange()" style="font-size: 18px; font-weight: 700; color: #059669;">
                    
                    <!-- Real-time calculation hint -->
                    <div id="payment_calculation_hint" style="margin-top: 8px; font-size: 13px;">
                        <span style="color: #059669; font-weight: 600;">✓ Exact Daily Installment Met: ₱{{ number_format($loan->daily_installment, 2) }}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Remarks / Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Day {{ $paidDays + 1 }} collection">
                </div>

                <!-- Client PIN Verification -->
                <div style="background: #f1f5f9; border: 1px solid var(--border-color); padding: 18px; border-radius: var(--radius-md); margin: 20px 0;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 14px; font-weight: 700; color: #0f172a;">
                            🔐 Client Verification: Enter Client 4-Digit PIN Code *
                        </label>
                        <p style="font-size: 12px; color: var(--text-secondary); margin-bottom: 8px;">
                            Hand device to client to verify and confirm payment transaction authorization.
                        </p>
                        <input type="password" name="client_pin" maxlength="4" class="form-control" placeholder="••••" required style="letter-spacing: 6px; font-size: 22px; text-align: center; max-width: 220px; background: white;">
                    </div>
                </div>

                <!-- Camera Photo Proof Capture with Timestamp -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 700;">📸 Live Camera Photo Proof with Timestamp Watermark *</label>
                    
                    <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                        <button type="button" class="btn btn-sm btn-outline" onclick="startCamera('colCameraVideo')">
                            📹 Turn On Camera
                        </button>
                        <button type="button" class="btn btn-sm btn-emerald" onclick="captureSnapshotWithTimestamp('colCameraVideo', 'colCanvas', 'collectorProofInput', 'colPreviewImg')">
                            📸 Capture Photo Proof
                        </button>
                    </div>

                    <div class="camera-container" style="max-height: 280px;">
                        <video id="colCameraVideo" autoplay playsinline></video>
                        <canvas id="colCanvas" style="display: none;"></canvas>
                    </div>

                    <!-- Captured Preview -->
                    <div style="margin-top: 12px;">
                        <img id="colPreviewImg" src="" alt="Captured Proof Preview" style="display: none; width: 100%; max-height: 200px; object-fit: contain; border-radius: var(--radius-sm); border: 2px solid #059669;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <a href="{{ route('collector.dashboard') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-emerald btn-lg">
                        ✓ Submit & Post Payment to Ledger
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/collector.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            onPaymentAmountChange();
        });
    </script>
@endpush
