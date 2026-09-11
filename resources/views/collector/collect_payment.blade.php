@extends('layouts.app')

@section('title', 'Process Client Payment')
@section('page_title', 'Collector - Process Client Daily Payment')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/collector.css') }}">
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="max-width: 800px; margin: 0 auto;">
        <!-- Client & Loan Info Card -->
        <div class="card" style="border-radius: var(--radius-xl);">
            <div class="card-header">
                <div>
                    <h3 class="card-title">{{ $client->user->name }}</h3>
                    <div style="font-size: 12.5px; color: var(--text-secondary);">
                        CP No: {{ $client->user->phone_number }} | Address: {{ $client->user->address }}
                    </div>
                </div>
                <span class="badge badge-emerald">Active Loan #{{ $loan->id }}</span>
            </div>

            @php
                $termDays = $loan->schedules ? ($loan->schedules->count() > 0 ? $loan->schedules->count() : ($loan->term_days ?? 60)) : 60;
                $totalInsuranceForTerm = $insuranceDaily * $termDays;
                $totalCombinedPayable = $loan->total_payable + $totalInsuranceForTerm;
                $totalCombinedPaid = (float)$loan->total_paid;
                $totalCombinedRemaining = max(0, $totalCombinedPayable - $totalCombinedPaid);
                $remainingInsurance = max(0, $totalInsuranceForTerm - ($paidDays * $insuranceDaily));
                $remainingLoan = max(0, $totalCombinedRemaining - $remainingInsurance);
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; background: #f8fafc; padding: 12px; border-radius: var(--radius-md); margin-bottom: 16px;">
                <div>
                    <span style="font-size: 11px; color: var(--text-secondary); display: block;">Daily Loan Premium</span>
                    <span style="font-size: 15px; font-weight: 700; color: #0f172a;">
                        ₱{{ number_format($loanPremiumDaily, 2) }}
                    </span>
                </div>

                <div>
                    <span style="font-size: 11px; color: var(--text-secondary); display: block;">Daily Insurance Premium</span>
                    <span style="font-size: 15px; font-weight: 700; color: #0284c7;">
                        ₱{{ number_format($insuranceDaily, 2) }}
                    </span>
                </div>

                <div>
                    <span style="font-size: 11px; color: var(--text-secondary); display: block;">Total Daily Due</span>
                    <span style="font-size: 16px; font-weight: 800; color: #d97706;" id="daily_installment_due" data-due="{{ $totalDailyPayable }}">
                        ₱{{ number_format($totalDailyPayable, 2) }}
                    </span>
                </div>

                <div>
                    <span style="font-size: 11px; color: var(--text-secondary); display: block;">Remaining Balance</span>
                    <span style="font-size: 15px; font-weight: 800; color: #059669;" id="client_remaining_balance" data-balance="{{ $totalCombinedRemaining }}">
                        ₱{{ number_format($totalCombinedRemaining, 2) }}
                    </span>
                    <div style="font-size: 9.5px; color: var(--text-secondary);">
                        (₱{{ number_format($remainingLoan, 2) }} + ₱{{ number_format($remainingInsurance, 2) }} Ins)
                    </div>
                </div>

                <div>
                    <span style="font-size: 11px; color: var(--text-secondary); display: block;">Progress</span>
                    <span style="font-size: 15px; font-weight: 700;">
                        {{ $paidDays }} / {{ $termDays }} Days
                    </span>
                </div>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 8px 12px; margin-bottom: 14px; font-size: 12px; color: #1e40af;">
                💡 <strong>Breakdown:</strong> Loan Premium (₱{{ number_format($loanPremiumDaily, 2) }}) + Insurance Premium (₱{{ number_format($insuranceDaily, 2) }}) = <strong>₱{{ number_format($totalDailyPayable, 2) }}</strong> Total Daily Due • Total Combined Payable: <strong>₱{{ number_format($totalCombinedPayable, 2) }}</strong>
            </div>

            @php
                $pendingWalletTx = $client->user && $client->user->walletTransactions ? $client->user->walletTransactions()->whereIn('status', ['pending_releasing_review', 'pending_host_approval', 'approved_by_host'])->latest()->first() : null;
            @endphp

            @if($pendingWalletTx)
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; padding: 12px 14px; border-radius: var(--radius-md); margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px;">
                    <div style="font-size: 18px; line-height: 1;">⏳</div>
                    <div style="flex: 1; font-size: 12.5px; color: #92400e;">
                        <strong>Pending {{ strtoupper(str_replace('_', ' ', $pendingWalletTx->type)) }} Request (₱{{ number_format($pendingWalletTx->amount, 2) }}):</strong> 
                        This client currently has a pending transaction awaiting Host Superadmin authorization. (This daily payment collection will post directly to their active loan balance).
                    </div>
                </div>
            @endif

            <!-- Payment Form -->
            <form action="{{ route('collector.payments.process', $loan->id) }}" method="POST" id="collectorPaymentForm" onsubmit="return validateCollectorPaymentForm()">
                @csrf
                <input type="hidden" name="photo_proof" id="collectorProofInput" required>

                <!-- Customizable Payment Amount Entry -->
                <div class="form-group">
                    <label class="form-label" for="payment_amount" style="font-size: 13.5px; font-weight: 700;">
                        Enter Payment Amount Received (₱) *
                    </label>
                    <input type="number" step="0.01" min="1" max="{{ $totalCombinedRemaining }}" name="amount_paid" id="payment_amount" class="form-control" value="{{ $totalDailyPayable }}" required oninput="onPaymentAmountChange()" style="font-size: 17px; font-weight: 700; color: #059669;">
                    
                    <!-- Real-time calculation hint -->
                    <div id="payment_calculation_hint" style="margin-top: 6px; font-size: 12.5px;">
                        <span style="color: #059669; font-weight: 600;">✓ Exact Daily Installment Met: ₱{{ number_format($totalDailyPayable, 2) }}</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Remarks / Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="e.g. Day {{ $paidDays + 1 }} collection">
                </div>

                <!-- Client PIN Verification -->
                <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 14px; border-radius: var(--radius-md); margin: 16px 0;">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label" style="font-size: 13.5px; font-weight: 700; color: #0f172a; display: flex; justify-content: space-between;">
                            <span>🔐 Client Verification: Enter Client 4-Digit PIN Code *</span>
                            <span style="font-size: 11.5px; color: var(--text-muted); font-weight: normal;">Hand phone to borrower</span>
                        </label>
                        <p style="font-size: 11.5px; color: var(--text-secondary); margin-bottom: 8px;">
                            Client must enter their confidential 4-digit PIN code to authorize this transaction.
                        </p>
                        <input type="password" name="client_pin" id="collector_client_pin" maxlength="4" inputmode="numeric" class="form-control" placeholder="••••" required style="letter-spacing: 8px; font-size: 22px; text-align: center; max-width: 200px; font-weight: 700; background: white; margin: 0 auto;">
                    </div>
                </div>

                <!-- Enhanced Camera Photo Proof Capture with Real-Time Stamped Preview -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" style="font-weight: 700; font-size: 13.5px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span>📸 Payment Handover Photo Proof (with Auto Timestamp) *</span>
                        <span style="font-size: 11.5px; color: #059669; font-weight: 600;">Camera Ready</span>
                    </label>

                    <!-- Option 1: Live Video Camera Viewport -->
                    <div id="camera-stream-wrapper" style="position: relative;">
                        <div class="camera-box-viewport">
                            <!-- Floating Quick Actions Bar -->
                            <div class="camera-top-toolbar">
                                <button type="button" class="camera-tool-pill" onclick="flipReleasingCamera('colCameraVideo')" title="Switch Front/Back Camera">
                                    🔄 Flip Camera
                                </button>
                                <button type="button" class="camera-tool-pill" id="releasing-torch-btn" onclick="toggleReleasingTorch()" style="display: none;" title="Toggle Flashlight">
                                    💡 Flash
                                </button>
                            </div>

                            <video id="colCameraVideo" autoplay playsinline muted></video>
                            <canvas id="colCanvas" style="display: none;"></canvas>

                            <!-- Big Mobile Camera Shutter Button -->
                            <div class="camera-shutter-bar">
                                <button type="button" class="camera-shutter-btn" onclick="captureSnapshotWithTimestamp('colCameraVideo', 'colCanvas', 'collectorProofInput', 'colPreviewImg')" title="Take Photo">
                                    <div class="camera-shutter-btn-inner">📸</div>
                                </button>
                            </div>
                        </div>

                        <!-- Native Phone Camera / Photo Upload Fallback Bar -->
                        <div style="text-align: center; margin-top: 10px;">
                            <label class="btn btn-outline btn-sm" style="font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <span>📱 Open Phone Camera App / Upload Photo</span>
                                <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'colCanvas', 'collectorProofInput', 'colPreviewImg')">
                            </label>
                        </div>
                    </div>

                    <!-- Option 2: Captured Photo Preview & Retake Bar (Visible immediately after capture) -->
                    <div id="photo-preview-wrapper" style="display: none;">
                        <div class="photo-preview-card">
                            <div style="font-size: 12px; color: #ffffff; font-weight: 600; margin-bottom: 6px; text-align: left;">
                                📷 Captured & Verified Photo Proof:
                            </div>
                            <img id="colPreviewImg" src="" alt="Captured Proof Preview">
                            <div class="photo-preview-actions">
                                <button type="button" class="btn btn-sm btn-outline" onclick="retakeReleasingPhoto('colCameraVideo', 'colPreviewImg', 'collectorProofInput')" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3);">
                                    🔄 Retake Photo
                                </button>
                                <label class="btn btn-sm btn-outline" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3); cursor: pointer;">
                                    📁 Choose Different Photo
                                    <input type="file" accept="image/*" capture="environment" style="display: none;" onchange="processUploadedProofImage(this, 'colCanvas', 'collectorProofInput', 'colPreviewImg')">
                                </label>
                            </div>
                        </div>
                        <div style="font-size: 12.5px; color: #059669; font-weight: 700; text-align: center; margin-top: 8px;">
                            ✓ Photo Verified with Official Timestamp & Ready for Submission!
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; flex-wrap: wrap;">
                    <a href="{{ route('collector.dashboard') }}" class="btn btn-outline" onclick="stopCamera()">Cancel</a>
                    <button type="submit" class="btn btn-emerald" style="font-weight: 700;">
                        ✓ Submit & Post Payment to Ledger
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/collector.js') }}"></script>
    <script src="{{ versioned_asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            onPaymentAmountChange();
            startCamera('colCameraVideo');
        });

        function validateCollectorPaymentForm() {
            const photoInput = document.getElementById('collectorProofInput');
            const pinInput = document.getElementById('collector_client_pin');

            if (!pinInput.value || pinInput.value.length !== 4) {
                showToast('error', 'Please enter a valid 4-digit client PIN.');
                pinInput.focus();
                return false;
            }

            if (!photoInput.value) {
                showToast('error', 'Please capture or upload photo proof before submitting payment.');
                return false;
            }

            return true;
        }
    </script>
@endpush
