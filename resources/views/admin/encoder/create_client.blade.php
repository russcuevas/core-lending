@extends('layouts.app')

@section('title', 'Encode New Client')
@section('page_title', 'Admin Encoder - Encode New Client Registration')

@push('styles')
    <link rel="stylesheet" href="{{ versioned_asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="max-width: 900px; margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Client Registration & 60-Day Loan Application</h3>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 3px 0 0 0;">
                        Upon submission, a unique Client QR Code and 60-Day Repayment Schedule will be automatically
                        generated.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.encoder.clients.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-row-2">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="client_name">Client Full Name *</label>
                        <input type="text" name="name" id="client_name" class="form-control @error('name') is-invalid @enderror"
                            placeholder="e.g. Juan Santos Dela Cruz" required value="{{ old('name') }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact No / Username -->
                    <div class="form-group">
                        <label class="form-label" for="phone_number">Contact Number (CP No / Login Username) *</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
                            placeholder="09171234567" required value="{{ old('phone_number') }}">
                        @error('phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Used as login username on client portal.</div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address (Optional)</label>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                            placeholder="client@example.com" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Default PIN Code -->
                    <div class="form-group">
                        <label class="form-label" for="pin_code">Default PIN Code *</label>
                        <input type="text" name="pin_code" id="pin_code" class="form-control @error('pin_code') is-invalid @enderror" value="{{ old('pin_code', '1234') }}"
                            maxlength="4" required style="font-weight: 700; letter-spacing: 2px;">
                        @error('pin_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-hint">Default is 1234. Client can reset via Email.</div>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="form-label" for="address">Complete Residential Address *</label>
                    <textarea name="address" id="address" class="form-control @error('address') is-invalid @enderror" rows="2"
                        placeholder="House No., Street, Barangay, City/Municipality" required>{{ old('address') }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Assigned Collector -->
                <div class="form-group">
                    <label class="form-label" for="collector_id">Assigned Field Collector (Who Referred / Collects)
                        *</label>
                    <select name="collector_id" id="collector_id" class="form-select @error('collector_id') is-invalid @enderror" required>
                        <option value="">-- Select Assigned Collector --</option>
                        @foreach ($collectors as $col)
                            <option value="{{ $col->id }}" {{ old('collector_id') == $col->id ? 'selected' : '' }}>
                                {{ $col->user->name }} ({{ $col->assigned_area ?? 'General Area' }})
                            </option>
                        @endforeach
                    </select>
                    @error('collector_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Valid ID Upload with Live Preview & Remove [✕] Button -->
                <div class="form-group">
                    <label class="form-label" for="valid_id">Client Valid Government ID (Image Upload)</label>
                    <input type="file" name="valid_id" id="valid_id" class="form-control image-upload-input @error('valid_id') is-invalid @enderror" accept="image/*"
                        data-preview-wrapper="valid_id_preview_wrapper" data-preview-img="valid_id_preview_img" data-preview-name="valid_id_preview_name">
                    @error('valid_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Upload valid government ID (JPG, PNG). Live preview will display below.</div>

                    <!-- Live Image Preview Container -->
                    <div class="image-preview-wrapper" id="valid_id_preview_wrapper" style="display: none;">
                        <div class="image-preview-box">
                            <button type="button" class="image-preview-remove-btn" onclick="removeImageUpload('valid_id', 'valid_id_preview_wrapper', 'valid_id_preview_img', 'valid_id_preview_name')" title="Remove this image">✕</button>
                            <img id="valid_id_preview_img" class="image-preview-img" src="" alt="Valid ID Preview">
                        </div>
                        <div class="image-preview-filename" id="valid_id_preview_name"></div>
                    </div>
                </div>

                <!-- Loan & Interest Configuration -->
                <div
                    style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                        <h4 style="font-size: 14.5px; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 6px;">
                            <span>💰</span> Loan Parameters & Computations
                        </h4>
                        <span class="badge badge-emerald" style="font-size: 11px; padding: 4px 10px;">
                            🔒 Locked to Host Global Settings
                        </span>
                    </div>

                    <!-- Row 1: Principal Input and Readonly Host Settings -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" for="principal_amount">Principal Loan Amount (₱) *</label>
                            <input type="number" step="0.01" name="loan_amount" id="principal_amount"
                                class="form-control @error('loan_amount') is-invalid @enderror" placeholder="e.g. 20000" required oninput="calculateLoanSchedule()"
                                value="{{ old('loan_amount') }}" style="font-size: 15px; font-weight: 600;">
                            @error('loan_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label class="form-label" for="interest_rate_percent">Interest Rate (%)</label>
                                <span style="font-size: 11px; color: var(--text-secondary);">Host Set</span>
                            </div>
                            <div style="position: relative;">
                                <input type="text" class="form-control" value="{{ number_format($defaultInterestRate, 2) }}% Fixed" readonly
                                    style="background: #f1f5f9; font-weight: 600; cursor: not-allowed;">
                                <input type="hidden" name="interest_rate_percent" id="interest_rate_percent" value="{{ $defaultInterestRate }}">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <label class="form-label">Loan Term Duration</label>
                                <span style="font-size: 11px; color: var(--text-secondary);">Host Set</span>
                            </div>
                            <input type="text" id="loan_term_days_display" data-term-days="{{ $defaultTermDays }}" class="form-control" value="{{ $defaultTermDays }} Days Fixed" readonly
                                style="background: #f1f5f9; font-weight: 600; cursor: not-allowed;">
                        </div>
                    </div>

                    <!-- Row 2: Live Computed Results in Highlighted Cards -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 12px 16px;">
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <span style="font-size: 12px; color: var(--text-secondary); margin-bottom: 2px;">Total Payable Amount (Principal + {{ number_format($defaultInterestRate, 1) }}% Interest)</span>
                            <span id="total_payable_display" style="font-size: 18px; font-weight: 700; color: #059669;">₱0.00</span>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: center; border-left: 1px solid #e2e8f0; padding-left: 16px;">
                            <span style="font-size: 12px; color: var(--text-secondary); margin-bottom: 2px;">Computed Daily Repayment ({{ $defaultTermDays }} Days)</span>
                            <span id="daily_installment_display" style="font-size: 18px; font-weight: 700; color: var(--text-primary);">₱0.00 / day</span>
                        </div>
                    </div>

                    <!-- System Commission Rules Summary -->
                    <div
                        style="margin-top: 14px; font-size: 12px; color: var(--text-secondary); line-height: 1.5; border-top: 1px dashed var(--border-color); padding-top: 10px;">
                        <strong>System Commission Rules Configured by Host:</strong>
                        <ul style="margin-left: 18px; margin-top: 4px; margin-bottom: 0;">
                            <li>₱{{ number_format($collectorLoanComm, 2) }} bonus commission automatically credited to Collector upon loan completion ({{ $defaultTermDays }} days fully paid).</li>
                            <li>{{ $collectorSavingsComm }}% automatic collector commission on any savings deposit opened by this client.</li>
                        </ul>
                    </div>
                </div>

                <!-- Repayment Schedule Preview -->
                <div class="form-group">
                    <label class="form-label">{{ $defaultTermDays }}-Day Loan Payment Schedule Preview</label>
                    <div class="schedule-grid-preview">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Day #</th>
                                    <th>Scheduled Date</th>
                                    <th>Expected Daily Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="schedule_preview_body">
                                <tr>
                                    <td colspan="4" class="text-center"
                                        style="padding: 16px; color: var(--text-muted);">
                                        Enter loan amount above to generate live {{ $defaultTermDays }}-day schedule preview.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; flex-wrap: wrap;">
                    <a href="{{ route('admin.encoder.dashboard') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-emerald">
                        Submit Client for Host Approval & Generate QR Card
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ versioned_asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            calculateLoanSchedule();
        });
    </script>
@endpush
