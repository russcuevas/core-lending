@extends('layouts.app')

@section('title', 'Encode New Client')
@section('page_title', 'Admin Encoder - Encode New Client Registration')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endpush

@section('content')
    <div style="max-width: 900px; margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Client Registration & 60-Day Loan Application</h3>
                    <p style="font-size: 13px; color: var(--text-secondary); margin: 4px 0 0 0;">
                        Upon submission, a unique Client QR Code and 60-Day Repayment Schedule will be automatically generated.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.encoder.clients.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="client_name">Client Full Name *</label>
                        <input type="text" name="name" id="client_name" class="form-control" placeholder="e.g. Juan Santos Dela Cruz" required value="{{ old('name') }}">
                    </div>

                    <!-- Contact No / Username -->
                    <div class="form-group">
                        <label class="form-label" for="phone_number">Contact Number (CP No / Login Username) *</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="09171234567" required value="{{ old('phone_number') }}">
                        <div class="form-hint">Used as login username on client portal.</div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address (Optional)</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="client@example.com" value="{{ old('email') }}">
                    </div>

                    <!-- Default PIN Code -->
                    <div class="form-group">
                        <label class="form-label" for="pin_code">Default PIN Code *</label>
                        <input type="text" name="pin_code" id="pin_code" class="form-control" value="1234" maxlength="4" required style="font-weight: 700; letter-spacing: 2px;">
                        <div class="form-hint">Default is 1234. Client can reset via SMS/Email.</div>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="form-label" for="address">Complete Residential Address *</label>
                    <textarea name="address" id="address" class="form-control" rows="2" placeholder="House No., Street, Barangay, City/Municipality" required>{{ old('address') }}</textarea>
                </div>

                <!-- Assigned Collector -->
                <div class="form-group">
                    <label class="form-label" for="collector_id">Assigned Field Collector (Who Referred / Collects) *</label>
                    <select name="collector_id" id="collector_id" class="form-select" required>
                        <option value="">-- Select Assigned Collector --</option>
                        @foreach($collectors as $col)
                            <option value="{{ $col->id }}" {{ old('collector_id') == $col->id ? 'selected' : '' }}>
                                {{ $col->user->name }} ({{ $col->assigned_area ?? 'General Area' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Valid ID Upload -->
                <div class="form-group">
                    <label class="form-label" for="valid_id">Client Valid Government ID (Image Upload)</label>
                    <input type="file" name="valid_id" id="valid_id" class="form-control" accept="image/*">
                    <div class="form-hint">Saved directly to public assets for quick verification.</div>
                </div>

                <!-- Loan & Interest Configuration -->
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; margin: 24px 0;">
                    <h4 style="font-size: 15px; margin-bottom: 16px; color: var(--text-primary);">
                        💰 Loan Terms & Commission Settings
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div class="form-group">
                            <label class="form-label" for="principal_amount">Principal Loan Amount (₱) *</label>
                            <input type="number" step="0.01" name="loan_amount" id="principal_amount" class="form-control" placeholder="e.g. 20000" required oninput="calculateLoanSchedule()">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="interest_rate_percent">Loan Interest Rate (%) *</label>
                            <input type="number" step="0.01" name="interest_rate_percent" id="interest_rate_percent" class="form-control" value="10" required oninput="calculateLoanSchedule()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Loan Term (Days)</label>
                            <input type="text" class="form-control" value="60 Days Fixed" readonly style="background: #f1f5f9;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Total Payable Amount</label>
                            <input type="text" id="total_payable_display" class="form-control" readonly style="background: #f1f5f9; font-weight: 700; color: #059669;" value="₱0.00">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Computed Daily Installment</label>
                            <input type="text" id="daily_installment_display" class="form-control" readonly style="background: #f1f5f9; font-weight: 700;" value="₱0.00">
                        </div>
                    </div>

                    <!-- System Commission Rules Summary -->
                    <div style="margin-top: 14px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; border-top: 1px dashed var(--border-color); padding-top: 12px;">
                        <strong>Standard Commission System Applied:</strong>
                        <ul style="margin-left: 20px;">
                            <li>₱300 bonus commission automatically credited to Collector upon loan completion (60 days fully paid).</li>
                            <li>5% automatic collector commission on any savings deposit opened by this client.</li>
                        </ul>
                    </div>
                </div>

                <!-- 60 Days Repayment Preview -->
                <div class="form-group">
                    <label class="form-label">60-Day Loan Payment Schedule Preview</label>
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
                                    <td colspan="4" class="text-center" style="padding: 20px; color: var(--text-muted);">
                                        Enter loan amount above to generate live 60-day schedule preview.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <a href="{{ route('admin.encoder.dashboard') }}" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-emerald btn-lg">
                        Submit Client for Host Approval & Generate QR Card
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            calculateLoanSchedule();
        });
    </script>
@endpush
