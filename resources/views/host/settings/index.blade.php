@extends('layouts.app')

@section('title', 'Global System Settings & Rates')
@section('page_title', 'Host Superadmin - Global System Settings & Rates')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}?v={{ file_exists(public_path('css/host.css')) ? filemtime(public_path('css/host.css')) : time() }}">
@endpush

@section('content')
    <!-- Security & Historical Integrity Notice -->
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-left: 4px solid #16a34a; border-radius: var(--radius-sm); padding: 14px 16px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; box-shadow: var(--shadow-sm);">
        <div style="font-size: 22px; line-height: 1;">🛡️</div>
        <div style="flex: 1;">
            <div style="font-weight: 700; color: #166534; font-size: 14px; margin-bottom: 3px;">
                Historical Record & Audit Trail Protection Active (Immutable Ledgers)
            </div>
            <p style="margin: 0; font-size: 12.5px; color: #15803d; line-height: 1.45;">
                Whenever you update the interest rates or parameters below, <strong>all previous and currently active historical loan records, payment receipts, and completed savings accounts remain 100% protected</strong> with their original mathematical computations. Newly encoded applications and new savings deposits moving forward will automatically adopt the new rates.
            </p>
        </div>
    </div>

    <form action="{{ route('host.settings.update') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- CARD 1: Loan Parameters -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">📈 Micro-Loan Parameters</h3>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                            Global defaults applied when Encoders create new client loan plans.
                        </p>
                    </div>
                    <span class="badge badge-emerald">Loan Engine</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Loan Interest Rate (%) *</label>
                    <input type="number" step="0.01" min="0" max="100" name="loan_interest_rate_percent" id="setting_loan_interest" class="form-control" value="{{ old('loan_interest_rate_percent', $loanInterest) }}" required oninput="updateLivePreview()">
                    <div class="form-hint">Standard fixed interest percentage (e.g. 10.00 for 10%).</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Default Repayment Term (Days) *</label>
                    <input type="number" min="1" max="365" name="loan_term_days" id="setting_loan_term" class="form-control" value="{{ old('loan_term_days', $loanTerm) }}" required oninput="updateLivePreview()">
                    <div class="form-hint">Daily installment cycle length (standard: 60 days).</div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; font-size: 12px;">
                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">Sample ₱10,000 Loan Preview:</div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <span style="color: var(--text-secondary);">Total Payable:</span>
                        <strong style="color: #059669;" id="preview_loan_total">₱11,000.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Daily Installment:</span>
                        <strong style="color: #d97706;" id="preview_loan_daily">₱183.33 / day</strong>
                    </div>
                </div>
            </div>

            <!-- CARD 2: Savings Parameters -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">🐖 Client Savings Fund Parameters</h3>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                            Return rate and lock-in period for client savings deposits.
                        </p>
                    </div>
                    <span class="badge badge-indigo">Savings Engine</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Client Savings Return Rate (%) *</label>
                    <input type="number" step="0.01" min="0" max="100" name="savings_interest_rate_percent" id="setting_savings_interest" class="form-control" value="{{ old('savings_interest_rate_percent', $savingsInterest) }}" required oninput="updateLivePreview()">
                    <div class="form-hint">Guaranteed total growth earned over the lock-in duration (e.g. 10.00 for 10%).</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Savings Lock-in Duration (Days) *</label>
                    <input type="number" min="1" max="365" name="savings_lock_in_days" id="setting_savings_lock_in" class="form-control" value="{{ old('savings_lock_in_days', $savingsLockIn) }}" required oninput="updateLivePreview()">
                    <div class="form-hint">Number of days client funds are locked to generate daily interest (standard: 60).</div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; font-size: 12px;">
                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">Sample ₱10,000 Savings Deposit Preview:</div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <span style="color: var(--text-secondary);">Daily Wallet Credit:</span>
                        <strong style="color: #059669;" id="preview_savings_daily">+₱16.67 / day</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--text-secondary);">Total Payout at Maturity:</span>
                        <strong style="color: #0284c7;" id="preview_savings_total">₱11,000.00</strong>
                    </div>
                </div>
            </div>

            <!-- CARD 3: Field Collector Commissions -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">🛵 Field Collector Commissions</h3>
                        <p style="font-size: 12px; color: var(--text-secondary); margin: 2px 0 0 0;">
                            Performance incentive credited to collectors upon loan completion & savings.
                        </p>
                    </div>
                    <span class="badge badge-amber">Commission Policy</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Fixed Commission per Fully-Paid Loan (₱) *</label>
                    <input type="number" step="0.01" min="0" name="collector_loan_commission_fixed" class="form-control" value="{{ old('collector_loan_commission_fixed', $collectorLoanComm) }}" required>
                    <div class="form-hint">Credited to collector wallet immediately when client clears 100% of loan balance.</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Collector Commission on Client Savings (%) *</label>
                    <input type="number" step="0.01" min="0" max="100" name="collector_savings_commission_percent" class="form-control" value="{{ old('collector_savings_commission_percent', $collectorSavingsComm) }}" required>
                    <div class="form-hint">Instant percentage credited to collector balance whenever assigned client opens savings (e.g. 5.00 for 5%).</div>
                </div>

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px; font-size: 12px;">
                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Automated Ledger Accounting:</div>
                    <p style="margin: 0; color: var(--text-secondary); line-height: 1.4;">
                        Commissions are tracked automatically in real time in the collector wallet ledger and Host Vault outflow accounts.
                    </p>
                </div>
            </div>
        </div>

        <!-- Action Submit Button Card -->
        <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="font-weight: 700; font-size: 14px;">Save & Apply Global Parameters</div>
                <div style="font-size: 12px; color: var(--text-secondary);">Changes take effect immediately for future transactions.</div>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                💾 Save Global Rates & Settings
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function updateLivePreview() {
            const loanRate = parseFloat(document.getElementById('setting_loan_interest').value) || 0;
            const loanDays = parseInt(document.getElementById('setting_loan_term').value) || 60;
            const samplePrincipal = 10000;
            const loanTotal = samplePrincipal * (1 + (loanRate / 100));
            const loanDaily = loanDays > 0 ? loanTotal / loanDays : 0;

            document.getElementById('preview_loan_total').innerText = '₱' + loanTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('preview_loan_daily').innerText = '₱' + loanDaily.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' / day';

            const savRate = parseFloat(document.getElementById('setting_savings_interest').value) || 0;
            const savDays = parseInt(document.getElementById('setting_savings_lock_in').value) || 60;
            const sampleDeposit = 10000;
            const totalSavInterest = sampleDeposit * (savRate / 100);
            const savDaily = savDays > 0 ? totalSavInterest / savDays : 0;
            const savTotal = sampleDeposit + totalSavInterest;

            document.getElementById('preview_savings_daily').innerText = '+₱' + savDaily.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' / day';
            document.getElementById('preview_savings_total').innerText = '₱' + savTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        document.addEventListener('DOMContentLoaded', updateLivePreview);
    </script>
@endpush