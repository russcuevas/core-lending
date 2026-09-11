@extends('layouts.app')

@section('title', 'Expenses Tracker')
@section('page_title', 'Admin Encoder - Operational Expenses Tracker')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin: 0;">Operational Expenses Tracker</h2>
            <p style="color: var(--text-secondary); margin: 2px 0 0 0; font-size: 12.5px;">Record and track daily operational expenditures. All expenses are deducted from Host Vault liquidity with verified receipt proof.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('admin.encoder.expenses.print') }}" target="_blank" class="btn btn-outline" style="font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <span>🖨</span> Print Expenses Sheet
            </a>
            <button type="button" class="btn btn-emerald" onclick="openModal('addExpenseModal')" style="font-weight: 700;">
                + Record New Expense
            </button>
        </div>
    </div>

    <!-- Expenses KPI Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid #e11d48; border-radius: var(--radius-lg); padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11.5px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Expenses Today</div>
            <div style="font-size: 22px; font-weight: 800; color: #e11d48; margin-top: 2px;">₱{{ number_format($expensesToday ?? 0, 2) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Today's operational outflows</div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid #d97706; border-radius: var(--radius-lg); padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11.5px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Expenses This Month</div>
            <div style="font-size: 22px; font-weight: 800; color: #d97706; margin-top: 2px;">₱{{ number_format($expensesMonth ?? 0, 2) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Current month expenditures</div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid #0284c7; border-radius: var(--radius-lg); padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11.5px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Expenses (All Time)</div>
            <div style="font-size: 22px; font-weight: 800; color: #0284c7; margin-top: 2px;">₱{{ number_format($expensesTotal ?? 0, 2) }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Cumulative recorded expenses</div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-left: 4px solid #7c3aed; border-radius: var(--radius-lg); padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 11.5px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Total Records</div>
            <div style="font-size: 22px; font-weight: 800; color: #7c3aed; margin-top: 2px;">{{ $expensesCount ?? $expenses->total() }}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Logged expense items</div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🧾 Expense Records</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Particulars / Description</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                        <th>Receipt Proof</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$expenses->isEmpty())
                        @foreach($expenses as $exp)
                            <tr>
                                <td>{{ $exp->date }}</td>
                                <td><strong>{{ $exp->particulars }}</strong></td>
                                <td><span class="badge badge-slate">{{ $exp->category }}</span></td>
                                <td style="font-weight: 700; color: #e11d48;">₱{{ number_format($exp->amount, 2) }}</td>
                                <td>{{ $exp->user->name ?? 'Admin' }}</td>
                                <td>
                                    @if($exp->receipt_image_path)
                                        <a href="{{ asset($exp->receipt_image_path) }}" target="_blank" class="btn btn-sm btn-outline">
                                            🔍 View Receipt
                                        </a>
                                    @else
                                        <span style="color: var(--text-muted); font-size: 12px;">No Receipt</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 20px; color: var(--text-muted);">No expenses recorded yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top: 14px;">
            {{ $expenses->links() }}
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div class="modal-overlay" id="addExpenseModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Record Operating Expense</h3>
                <button type="button" class="modal-close" onclick="closeModal('addExpenseModal')">&times;</button>
            </div>
            <form action="{{ route('admin.encoder.expenses.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Expense Date *</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Particulars (Description) *</label>
                        <input type="text" name="particulars" class="form-control @error('particulars') is-invalid @enderror" value="{{ old('particulars') }}" placeholder="e.g. Office Bond Paper, QR Laminating Film" required>
                        @error('particulars')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="Office Supplies" {{ old('category') == 'Office Supplies' ? 'selected' : '' }}>Office Supplies</option>
                            <option value="Transportation / Fuel" {{ old('category') == 'Transportation / Fuel' ? 'selected' : '' }}>Transportation / Fuel</option>
                            <option value="Utilities" {{ old('category') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                            <option value="Representation" {{ old('category') == 'Representation' ? 'selected' : '' }}>Representation</option>
                            <option value="Miscellaneous" {{ old('category') == 'Miscellaneous' ? 'selected' : '' }}>Miscellaneous</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount (₱) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="0.00" required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Receipt Photo Proof</label>
                        <input type="file" id="expense_receipt_input" name="receipt" class="form-control image-upload-input @error('receipt') is-invalid @enderror" accept="image/*" data-preview-wrapper="receipt_preview_wrapper" data-preview-img="receipt_preview_img" data-preview-name="receipt_preview_name">
                        <div class="image-preview-wrapper" id="receipt_preview_wrapper" style="display: none;">
                            <div class="image-preview-box">
                                <img src="" id="receipt_preview_img" class="image-preview-img" alt="Receipt Preview">
                                <button type="button" class="image-preview-remove-btn" title="Remove image" onclick="removeImageUpload('expense_receipt_input', 'receipt_preview_wrapper', 'receipt_preview_img', 'receipt_preview_name')">✕</button>
                            </div>
                            <div class="image-preview-filename" id="receipt_preview_name"></div>
                        </div>
                        @error('receipt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addExpenseModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
@endsection
