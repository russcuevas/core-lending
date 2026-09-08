@extends('layouts.app')

@section('title', 'Host Vault & Ledgers')
@section('page_title', 'Host Superadmin - Host Vault & System Ledgers')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <!-- Host Vault Hero Card -->
    <div class="vault-hero-card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <div>
            <div class="vault-title">Official Host Vault Balance</div>
            <div class="vault-amount">₱{{ number_format($vaultBalance, 2) }}</div>
            <div style="font-size: 12.5px; color: #ffffff; opacity: 0.9; margin-top: 4px; font-weight: 500;">Real-time available corporate liquidity</div>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-emerald" onclick="openModal('addVaultFundsModal')" style="box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                <span>+</span> Add Funds / Transaction
            </button>
            <button type="button" class="btn" onclick="openModal('adjustVaultBalanceModal')" style="background: rgba(255,255,255,0.22); color: #ffffff; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(4px);">
                <span>✏️</span> Edit Vault Balance
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card">
        <form method="GET" action="{{ route('host.transactions.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) auto; gap: 12px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Flow Type</label>
                <select name="type" class="form-select">
                    <option value="">All Types (In & Out)</option>
                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Cash In (Inflow)</option>
                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Cash Out (Outflow)</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="capital_deposit" {{ request('category') == 'capital_deposit' ? 'selected' : '' }}>Capital Deposit</option>
                    <option value="cash_in" {{ request('category') == 'cash_in' ? 'selected' : '' }}>Cash In</option>
                    <option value="cash_out" {{ request('category') == 'cash_out' ? 'selected' : '' }}>Cash Out</option>
                    <option value="loan_release" {{ request('category') == 'loan_release' ? 'selected' : '' }}>Loan Disbursement</option>
                    <option value="loan_repayment" {{ request('category') == 'loan_repayment' ? 'selected' : '' }}>Loan Repayment</option>
                    <option value="savings_deposit" {{ request('category') == 'savings_deposit' ? 'selected' : '' }}>Savings Deposit</option>
                    <option value="expense" {{ request('category') == 'expense' ? 'selected' : '' }}>Expense</option>
                    <option value="vault_adjustment" {{ request('category') == 'vault_adjustment' ? 'selected' : '' }}>Vault Adjustment</option>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">From Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>

            <div class="form-group" style="margin: 0;">
                <label class="form-label">To Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>

            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-emerald">Filter</button>
                <a href="{{ route('host.transactions.index') }}" class="btn btn-outline">Reset</a>
            </div>
        </form>
    </div>

    <!-- Ledgers Table -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title">📖 Comprehensive Audit Trail & Vault History</h3>
            <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addVaultFundsModal')">
                + Add Transaction
            </button>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Flow</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description / Reference</th>
                        <th>Vault Balance After</th>
                        <th>Officer</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$ledgers->isEmpty())
                        @foreach($ledgers as $l)
                            <tr>
                                <td>#{{ $l->id }}</td>
                                <td>{{ $l->created_at->format('Y-m-d h:i A') }}</td>
                                <td>
                                    @if($l->type === 'in')
                                        <span class="badge badge-emerald">Cash In (+)</span>
                                    @else
                                        <span class="badge badge-amber">Cash Out (-)</span>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight:600; text-transform:uppercase; font-size:11px;">
                                        {{ str_replace('_', ' ', $l->category) }}
                                    </span>
                                </td>
                                <td style="font-weight:700; color: {{ $l->type === 'in' ? '#059669' : '#d97706' }};">
                                    {{ $l->type === 'in' ? '+' : '-' }}₱{{ number_format($l->amount, 2) }}
                                </td>
                                <td>{{ $l->description ?? 'N/A' }}</td>
                                <td style="font-weight:700;">₱{{ number_format($l->vault_balance_after, 2) }}</td>
                                <td>{{ $l->creator->name ?? 'System' }}</td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 11.5px;" onclick="openEditLedgerModal('{{ $l->id }}', '{{ $l->category }}', '{{ addslashes($l->description) }}')">
                                        ✏️ Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 20px; color: var(--text-muted);">No records found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top: 14px;">
            {{ $ledgers->links() }}
        </div>
    </div>

    <!-- MODAL 1: Add Vault Funds / Manual Inflow or Outflow -->
    <div class="modal-overlay" id="addVaultFundsModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">💰 Add Host Vault Transaction</h3>
                <button type="button" class="modal-close" onclick="closeModal('addVaultFundsModal')">&times;</button>
            </div>
            <form action="{{ route('host.transactions.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                        Record a manual capital deposit, cash infusion, or corporate expenditure into the official Vault Ledger.
                    </p>

                    <div class="form-group">
                        <label class="form-label">Transaction Flow Type *</label>
                        <select name="type" id="vault_tx_type" class="form-select" required onchange="updateCategoryOptions()">
                            <option value="in">🟢 Cash In (Deposit / Capital Infusion / Inflow)</option>
                            <option value="out">🔴 Cash Out (Withdrawal / Capital Outflow / Expense)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount (₱) *</label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="e.g. 50000.00" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" id="vault_tx_category" class="form-select" required>
                            <option value="capital_deposit">Capital Deposit / Top-up</option>
                            <option value="cash_in">Cash In</option>
                            <option value="loan_repayment">Loan Repayment Collection</option>
                            <option value="savings_deposit">Savings Deposit</option>
                            <option value="vault_adjustment">Vault Adjustment</option>
                            <option value="other_inflow">Other Inflow</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Note *</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="e.g. Initial company working capital injection" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addVaultFundsModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Record Vault Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Adjust / Edit Vault Balance Directly -->
    <div class="modal-overlay" id="adjustVaultBalanceModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Edit / Adjust Host Vault Balance</h3>
                <button type="button" class="modal-close" onclick="closeModal('adjustVaultBalanceModal')">&times;</button>
            </div>
            <form action="{{ route('host.transactions.adjust_balance') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div style="background: #f1f5f9; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 14px;">
                        <span style="font-size: 12px; color: var(--text-secondary); display: block;">Current Vault Balance:</span>
                        <strong style="font-size: 18px; color: var(--brand-dark);">₱{{ number_format($vaultBalance, 2) }}</strong>
                    </div>

                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px;">
                        Enter the new target Vault Balance. The system will automatically compute the exact adjustment delta and record an audited ledger entry.
                    </p>

                    <div class="form-group">
                        <label class="form-label">New Target Vault Balance (₱) *</label>
                        <input type="number" step="0.01" min="0" name="target_balance" class="form-control" placeholder="e.g. 1500000.00" value="{{ $vaultBalance }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Balance Adjustment *</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Starting capital calibration / Audit reconciliation" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('adjustVaultBalanceModal')">Cancel</button>
                    <button type="submit" class="btn btn-emerald">Save New Vault Balance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Edit Existing Ledger Entry -->
    <div class="modal-overlay" id="editVaultLedgerModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">✏️ Edit Ledger Entry #<span id="edit_ledger_id_display"></span></h3>
                <button type="button" class="modal-close" onclick="closeModal('editVaultLedgerModal')">&times;</button>
            </div>
            <form id="edit_ledger_form" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" id="edit_ledger_category" class="form-select" required>
                            <option value="capital_deposit">Capital Deposit / Top-up</option>
                            <option value="cash_in">Cash In</option>
                            <option value="cash_out">Cash Out</option>
                            <option value="loan_release">Loan Disbursement</option>
                            <option value="loan_repayment">Loan Repayment</option>
                            <option value="savings_deposit">Savings Deposit</option>
                            <option value="expense">Operating Expense</option>
                            <option value="vault_adjustment">Vault Adjustment</option>
                            <option value="capital_withdrawal">Capital Withdrawal</option>
                            <option value="other_inflow">Other Inflow</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description / Reference Note *</label>
                        <textarea name="description" id="edit_ledger_description" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editVaultLedgerModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Ledger Entry</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function updateCategoryOptions() {
            const type = document.getElementById('vault_tx_type').value;
            const categorySelect = document.getElementById('vault_tx_category');
            if (type === 'in') {
                categorySelect.innerHTML = `
                    <option value="capital_deposit">Capital Deposit / Top-up</option>
                    <option value="cash_in">Cash In</option>
                    <option value="loan_repayment">Loan Repayment Collection</option>
                    <option value="savings_deposit">Savings Deposit</option>
                    <option value="vault_adjustment">Vault Adjustment</option>
                    <option value="other_inflow">Other Inflow</option>
                `;
            } else {
                categorySelect.innerHTML = `
                    <option value="cash_out">Cash Out / Disbursed</option>
                    <option value="loan_release">Loan Disbursement</option>
                    <option value="expense">Operating Expense</option>
                    <option value="capital_withdrawal">Capital Withdrawal</option>
                    <option value="vault_adjustment">Vault Adjustment</option>
                    <option value="other_outflow">Other Outflow</option>
                `;
            }
        }

        function openEditLedgerModal(id, category, description) {
            document.getElementById('edit_ledger_id_display').innerText = id;
            document.getElementById('edit_ledger_category').value = category;
            document.getElementById('edit_ledger_description').value = description;
            document.getElementById('edit_ledger_form').action = `/host/transactions/${id}`;
            openModal('editVaultLedgerModal');
        }
    </script>
@endpush
