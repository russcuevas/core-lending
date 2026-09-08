@extends('layouts.app')

@section('title', 'Expenses Tracker')
@section('page_title', 'Admin Encoder - Operational Expenses Tracker')

@section('content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <p style="color: var(--text-secondary); margin: 0;">Record and track daily operational expenditures with receipt proof.</p>
        <button type="button" class="btn btn-emerald" onclick="openModal('addExpenseModal')">
            + Record New Expense
        </button>
    </div>

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🧾 Expense Records</h3>
        </div>
        <div class="table-responsive">
            <table class="data-table">
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
                    @forelse($expenses as $exp)
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
                                    <span style="color: var(--text-muted); font-size:12px;">No Receipt</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 24px; color: var(--text-muted);">No expenses recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 16px;">
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
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Particulars (Description) *</label>
                        <input type="text" name="particulars" class="form-control" placeholder="e.g. Office Bond Paper, QR Laminating Film" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-select" required>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Transportation / Fuel">Transportation / Fuel</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Representation">Representation</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount (₱) *</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Receipt Photo Proof</label>
                        <input type="file" name="receipt" class="form-control" accept="image/*">
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
