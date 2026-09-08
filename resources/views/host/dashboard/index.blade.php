@extends('layouts.app')

@section('title', 'Host Superadmin Dashboard')
@section('page_title', 'Host Superadmin Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/host.css') }}">
@endpush

@section('content')
    <!-- Host Vault Hero Card -->
    <div class="vault-hero-card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <div class="vault-title">Official Host Vault Balance</div>
            <div class="vault-amount">₱{{ number_format($vaultBalance, 2) }}</div>
            <div style="font-size: 12.5px; color: #ffffff; opacity: 0.9; margin-top: 4px; font-weight: 500;">Real-time available corporate funds</div>
            <div style="display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addVaultFundsModal')" style="box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                    <span>+</span> Add Funds / Transaction
                </button>
                <button type="button" class="btn btn-sm" onclick="openModal('adjustVaultBalanceModal')" style="background: rgba(255,255,255,0.22); color: #ffffff; border: 1px solid rgba(255,255,255,0.4); backdrop-filter: blur(4px);">
                    <span>✏️</span> Edit Vault Balance
                </button>
            </div>
        </div>
        <div class="vault-stats-row">
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Active Loans</div>
                <div class="vault-stat-subval">₱{{ number_format($totalActiveLoans, 2) }}</div>
            </div>
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Active Savings</div>
                <div class="vault-stat-subval">₱{{ number_format($totalSavingsActive, 2) }}</div>
            </div>
            <div class="vault-stat-item">
                <div class="vault-stat-sublabel">Borrowers</div>
                <div class="vault-stat-subval">{{ $totalActiveClientsCount }} Clients</div>
            </div>
        </div>
    </div>

    <!-- Daily Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon emerald">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Cash In</div>
                <div class="stat-value">₱{{ number_format($todayCashIn, 2) }}</div>
                <div class="stat-subtext">Received into host vault</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Cash Out</div>
                <div class="stat-value">₱{{ number_format($todayCashOut, 2) }}</div>
                <div class="stat-subtext">Disbursed from vault</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Loan Collections</div>
                <div class="stat-value">₱{{ number_format($todayLoanCollections, 2) }}</div>
                <div class="stat-subtext">From daily field payments</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rose">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
            </div>
            <div class="stat-info">
                <div class="stat-label">Today Expenses</div>
                <div class="stat-value">₱{{ number_format($todayExpenses, 2) }}</div>
                <div class="stat-subtext">Operating expenditures</div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Alert Banner -->
    @if($totalPendingApprovals > 0)
        <div style="background: var(--amber-light); border-left: 4px solid var(--amber); padding: 14px 16px; border-radius: var(--radius-md); margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 22px;">⚠</span>
                <div>
                    <strong style="color: #92400e; font-size: 13.5px;">{{ $totalPendingApprovals }} Pending Items Requiring Host Approval</strong>
                    <div style="font-size: 12px; color: #b45309;">
                        Loans ({{ $pendingLoanCount }}), Cash Requests ({{ $pendingWalletCount }}), Collectors ({{ $pendingCollectorCount }}), Updates ({{ $pendingClientUpdatesCount }})
                    </div>
                </div>
            </div>
            <a href="{{ route('host.approvals.index') }}" class="btn btn-sm btn-amber">Review All &rarr;</a>
        </div>
    @endif

    <!-- Weekly Chart & Recent Ledger Grid -->
    <div class="host-grid-2-1">
        <!-- Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Weekly Cash Movement Trend</h3>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="dailyTransactionChart"></canvas>
            </div>
        </div>

        <!-- Quick Summary & Access -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">System Operations</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('host.approvals.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Master Approvals Hub</span>
                    <span class="badge badge-amber">{{ $totalPendingApprovals }}</span>
                </a>
                <a href="{{ route('host.accounts.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Create User Credentials</span>
                    <span>+</span>
                </a>
                <a href="{{ route('host.transactions.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Host Vault Audit Trail</span>
                    <span>&rarr;</span>
                </a>
                <a href="{{ route('host.reports.index') }}" class="btn btn-outline" style="justify-content: space-between;">
                    <span>Financial Reports</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Vault Ledger Entries -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 class="card-title">Recent Host Vault Transactions</h3>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin: 2px 0 0 0;">Latest entries affecting corporate liquidity</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-sm btn-emerald" onclick="openModal('addVaultFundsModal')">
                    + Add Transaction
                </button>
                <a href="{{ route('host.transactions.index') }}" class="btn btn-sm btn-outline">View All &rarr;</a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="data-table data-table-enhanced">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>Vault Balance After</th>
                        <th>Processed By</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!$recentTransactions->isEmpty())
                        @foreach($recentTransactions as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    @if($tx->type === 'in')
                                        <span class="badge badge-emerald">Cash In</span>
                                    @else
                                        <span class="badge badge-amber">Cash Out</span>
                                    @endif
                                </td>
                                <td><span style="font-weight: 600; text-transform: uppercase; font-size: 11px;">{{ str_replace('_', ' ', $tx->category) }}</span></td>
                                <td style="font-weight: 700; color: {{ $tx->type === 'in' ? '#059669' : '#d97706' }};">
                                    {{ $tx->type === 'in' ? '+' : '-' }}₱{{ number_format($tx->amount, 2) }}
                                </td>
                                <td>{{ $tx->description }}</td>
                                <td style="font-weight: 700;">₱{{ number_format($tx->vault_balance_after, 2) }}</td>
                                <td>{{ $tx->creator->name ?? 'System' }}</td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 11.5px;" onclick="openEditLedgerModal('{{ $tx->id }}', '{{ $tx->category }}', '{{ addslashes($tx->description) }}')">
                                        ✏️ Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="8" class="text-center" style="padding: 20px; color: var(--text-muted);">No transactions recorded yet.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
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
    <script src="{{ asset('js/host.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            initHostCharts({
                labels: {!! json_encode($chartLabels) !!},
                cashIn: {!! json_encode($chartCashIn) !!},
                cashOut: {!! json_encode($chartCashOut) !!},
                collections: {!! json_encode($chartCollections) !!}
            });
        });

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
