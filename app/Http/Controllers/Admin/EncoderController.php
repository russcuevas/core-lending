<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Client;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\Expense;
use App\Models\HostVaultLedger;
use App\Models\ClientUpdateRequest;
use App\Models\SystemNotification;
use App\Models\SystemSetting;
use Carbon\Carbon;

class EncoderController extends Controller
{
    public function dashboard()
    {
        $totalClientsEncoded = Client::count();
        $pendingHostApprovals = Loan::where('status', 'pending_host_approval')->count();
        $recentClients = Client::with(['user', 'collector.user', 'currentLoan'])->latest()->take(10)->get();
        $collectors = Collector::with('user')->whereHas('user', function ($q) {
            $q->where('status', 'active');
        })->get();
        $expensesToday = Expense::whereDate('date', Carbon::today())->sum('amount');

        return view('admin.encoder.dashboard', compact(
            'totalClientsEncoded',
            'pendingHostApprovals',
            'recentClients',
            'collectors',
            'expensesToday'
        ));
    }

    public function createClient()
    {
        $collectors = Collector::with('user')->whereHas('user', function ($q) {
            $q->where('status', 'active');
        })->get();

        $defaultInterestRate = SystemSetting::get('loan_interest_rate_percent', 10.00);
        $defaultTermDays = (int)SystemSetting::get('loan_term_days', 60);
        $defaultInsurancePremium = (float)SystemSetting::get('loan_insurance_premium_daily', 5.00);
        $collectorLoanComm = SystemSetting::get('collector_loan_commission_fixed', 300.00);
        $collectorSavingsComm = SystemSetting::get('collector_savings_commission_percent', 5.00);

        return view('admin.encoder.create_client', compact(
            'collectors',
            'defaultInterestRate',
            'defaultTermDays',
            'defaultInsurancePremium',
            'collectorLoanComm',
            'collectorSavingsComm'
        ));
    }

    public function storeClient(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email' => 'nullable|email|unique:users,email',
            'address' => 'required|string',
            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_phone' => 'required|string|max:50',
            'valid_id' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'loan_amount' => 'required|numeric|min:500',
            'interest_rate_percent' => 'nullable|numeric|min:0',
            'collector_id' => 'required|exists:collectors,id',
            'pin_code' => 'nullable|digits:4',
        ]);

        // Upload valid ID directly to public/uploads/id_proofs
        $validIdPath = null;
        if ($request->hasFile('valid_id')) {
            $file = $request->file('valid_id');
            $uploadDir = public_path('uploads/id_proofs');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'id_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $validIdPath = 'uploads/id_proofs/' . $filename;
        }

        $pinCode = $request->filled('pin_code') ? $request->pin_code : '1234';

        // 1. Create User account for client
        $user = User::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->filled('email') ? $request->email : null,
            'password' => Hash::make($pinCode),
            'pin_code' => $pinCode,
            'role' => 'client',
            'address' => $request->address,
            'valid_id_path' => $validIdPath,
            'status' => 'pending_host_approval',
        ]);

        // 2. Generate Unique QR Code Token
        $qrToken = 'CLIENT-QR-' . strtoupper(Str::random(10));

        $client = Client::create([
            'user_id' => $user->id,
            'collector_id' => $request->collector_id,
            'qr_code_token' => $qrToken,
            'wallet_balance' => 0.00,
            'beneficiary_name' => $request->beneficiary_name,
            'beneficiary_phone' => $request->beneficiary_phone,
            'status' => 'pending_host_approval',
        ]);

        // 3. Calculate Loan Details (Enforced from Host Global Settings)
        $termDays = (int)SystemSetting::get('loan_term_days', 60);
        $interestPercent = (float)SystemSetting::get('loan_interest_rate_percent', 10.00);
        $insuranceDaily = (float)SystemSetting::get('loan_insurance_premium_daily', 5.00);
        $principal = (float)$request->loan_amount;
        $interestTotal = $principal * ($interestPercent / 100);
        $totalPayable = $principal + $interestTotal;
        $loanPremiumDaily = round($totalPayable / $termDays, 2);
        $totalDailyPayable = $loanPremiumDaily + $insuranceDaily;

        $loan = Loan::create([
            'client_id' => $client->id,
            'collector_id' => $request->collector_id,
            'principal_amount' => $principal,
            'interest_rate_percent' => $interestPercent,
            'total_payable' => $totalPayable,
            'daily_installment' => $loanPremiumDaily,
            'loan_premium_daily' => $loanPremiumDaily,
            'insurance_premium_daily' => $insuranceDaily,
            'total_daily_payable' => $totalDailyPayable,
            'term_days' => $termDays,
            'remaining_balance' => $totalPayable,
            'total_paid' => 0.00,
            'status' => 'pending_host_approval',
            'encoder_id' => Auth::id(),
        ]);

        $client->update(['current_loan_id' => $loan->id]);

        // 4. Generate Loan Payment Schedule based on Term Days
        $today = Carbon::today();
        for ($day = 1; $day <= $termDays; $day++) {
            $expectedForDay = ($day === $termDays)
                ? round($totalPayable - ($loanPremiumDaily * ($termDays - 1)), 2)
                : $loanPremiumDaily;

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'day_number' => $day,
                'due_date' => $today->copy()->addDays($day)->format('Y-m-d'),
                'expected_amount' => $expectedForDay,
                'paid_amount' => 0.00,
                'status' => 'unpaid',
            ]);
        }

        // 5. Notify Superadmin / Host for Approval
        SystemNotification::sendNotification(
            null,
            'host',
            'New Client Loan Application',
            "Admin Encoder submitted client application for {$user->name} (₱" . number_format($principal, 2) . "). Waiting for Host approval.",
            'approval_needed',
            '/host/approvals'
        );

        return redirect()->route('admin.encoder.print_qr', $client->id)->with('success', "Client encoded successfully! 60-day payment schedule generated. Ready to print QR & Card.");
    }

    public function printClientQr(Client $client)
    {
        $client->load(['user', 'collector.user', 'currentLoan.schedules']);
        return view('admin.encoder.print_client_card', compact('client'));
    }

    public function clientList(Request $request)
    {
        $query = Client::with(['user', 'collector.user', 'currentLoan'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $clients = $query->paginate(20);
        $collectors = Collector::with('user')->whereHas('user', function ($q) {
            $q->where('status', 'active');
        })->get();

        $defaultInterestRate = SystemSetting::get('loan_interest_rate_percent', 10.00);
        $defaultTermDays = (int)SystemSetting::get('loan_term_days', 60);
        $defaultInsurancePremium = (float)SystemSetting::get('loan_insurance_premium_daily', 5.00);

        return view('admin.encoder.clients', compact('clients', 'collectors', 'defaultInterestRate', 'defaultTermDays', 'defaultInsurancePremium'));
    }

    public function renewLoan(Request $request, Client $client)
    {
        $request->validate([
            'loan_amount' => 'required|numeric|min:500',
            'collector_id' => 'required|exists:collectors,id',
            'notes' => 'nullable|string',
        ]);

        // Check if client currently has an active loan in progress
        if ($client->currentLoan && in_array($client->currentLoan->status, ['active', 'pending_host_approval', 'approved_for_release'])) {
            return back()->with('error', 'Client already has an active or pending loan application in progress.');
        }

        // Calculate Loan Details strictly based on Host Global Settings
        $termDays = (int)SystemSetting::get('loan_term_days', 60);
        $interestPercent = (float)SystemSetting::get('loan_interest_rate_percent', 10.00);
        $insuranceDaily = (float)SystemSetting::get('loan_insurance_premium_daily', 5.00);
        $principal = (float)$request->loan_amount;
        $interestTotal = $principal * ($interestPercent / 100);
        $totalPayable = $principal + $interestTotal;
        $loanPremiumDaily = round($totalPayable / $termDays, 2);
        $totalDailyPayable = $loanPremiumDaily + $insuranceDaily;

        // Update collector and client status
        $client->update([
            'collector_id' => $request->collector_id,
            'status' => 'pending_host_approval',
        ]);

        $loan = Loan::create([
            'client_id' => $client->id,
            'collector_id' => $request->collector_id,
            'principal_amount' => $principal,
            'interest_rate_percent' => $interestPercent,
            'total_payable' => $totalPayable,
            'daily_installment' => $loanPremiumDaily,
            'loan_premium_daily' => $loanPremiumDaily,
            'insurance_premium_daily' => $insuranceDaily,
            'total_daily_payable' => $totalDailyPayable,
            'term_days' => $termDays,
            'remaining_balance' => $totalPayable,
            'total_paid' => 0.00,
            'status' => 'pending_host_approval',
            'encoder_id' => Auth::id(),
            'release_note' => $request->notes ?? "Loan Renewal / Reloan application.",
        ]);

        $client->update(['current_loan_id' => $loan->id]);

        // Generate Loan Payment Schedule
        for ($day = 1; $day <= $termDays; $day++) {
            $expectedForDay = ($day === $termDays)
                ? round($totalPayable - ($loanPremiumDaily * ($termDays - 1)), 2)
                : $loanPremiumDaily;

            LoanSchedule::create([
                'loan_id' => $loan->id,
                'day_number' => $day,
                'due_date' => null,
                'expected_amount' => $expectedForDay,
                'paid_amount' => 0.00,
                'status' => 'unpaid',
            ]);
        }

        // Notify Host Superadmin
        SystemNotification::sendNotification(
            null,
            'host',
            'Loan Renewal Application',
            "Admin Encoder submitted Loan Renewal for {$client->user->name} (₱" . number_format($principal, 2) . ") for Host Approval.",
            'approval_needed',
            '/host/approvals'
        );

        return back()->with('success', "Loan renewal for {$client->user->name} (₱" . number_format($principal, 2) . ") submitted to Host for approval!");
    }

    public function requestClientUpdate(Request $request, Client $client)
    {
        $request->validate([
            'name' => 'required|string',
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'beneficiary_name' => 'nullable|string|max:255',
            'beneficiary_phone' => 'nullable|string|max:50',
            'collector_id' => 'required|exists:collectors,id',
            'notes' => 'nullable|string',
        ]);

        $oldData = [
            'name' => $client->user->name,
            'phone_number' => $client->user->phone_number,
            'address' => $client->user->address,
            'beneficiary_name' => $client->beneficiary_name,
            'beneficiary_phone' => $client->beneficiary_phone,
            'collector_id' => $client->collector_id,
        ];

        $newData = [
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'beneficiary_name' => $request->beneficiary_name,
            'beneficiary_phone' => $request->beneficiary_phone,
            'collector_id' => $request->collector_id,
        ];

        ClientUpdateRequest::create([
            'client_id' => $client->id,
            'requested_by' => Auth::id(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'status' => 'pending_host_approval',
            'notes' => $request->notes,
        ]);

        SystemNotification::sendNotification(
            null,
            'host',
            'Client Details Update Request',
            "Admin Encoder requested profile update for client {$client->user->name}.",
            'approval_needed',
            '/host/approvals'
        );

        return back()->with('success', 'Update request submitted to Superadmin for approval.');
    }

    public function expensesIndex()
    {
        $today = Carbon::today();
        $expenses = Expense::with('user')->latest()->paginate(20);
        $expensesToday = Expense::whereDate('date', $today)->sum('amount');
        $expensesMonth = Expense::whereMonth('date', $today->month)->whereYear('date', $today->year)->sum('amount');
        $expensesTotal = Expense::sum('amount');
        $expensesCount = Expense::count();

        return view('admin.encoder.expenses', compact(
            'expenses',
            'expensesToday',
            'expensesMonth',
            'expensesTotal',
            'expensesCount'
        ));
    }

    public function printExpenses(Request $request)
    {
        $query = Expense::with('user')->orderBy('date', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $expenses = $query->get();
        $totalAmount = $expenses->sum('amount');
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $selectedCategory = $request->category;

        return view('admin.encoder.print_expenses', compact(
            'expenses',
            'totalAmount',
            'startDate',
            'endDate',
            'selectedCategory'
        ));
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'particulars' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'category' => 'required|string',
            'receipt' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $uploadDir = public_path('uploads/expenses');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'exp_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $receiptPath = 'uploads/expenses/' . $filename;
        }

        $expense = Expense::create([
            'user_id' => Auth::id(),
            'date' => $request->date,
            'particulars' => $request->particulars,
            'amount' => (float)$request->amount,
            'category' => $request->category,
            'receipt_image_path' => $receiptPath,
        ]);

        // Deduct from Host Vault & record entry in HostVaultLedger
        HostVaultLedger::logEntry(
            'out',
            'expense',
            (float)$expense->amount,
            "Operating Expense: {$expense->particulars} (Category: {$expense->category}, Encoded by: " . Auth::user()->name . ")",
            'Expense',
            $expense->id,
            Auth::id()
        );

        // Notify Host Superadmin
        SystemNotification::sendNotification(
            null,
            'host',
            "🧾 Operating Expense Recorded (₱" . number_format($expense->amount, 2) . ")",
            "Admin Encoder " . Auth::user()->name . " logged an operating expense: {$expense->particulars} (₱" . number_format($expense->amount, 2) . "). Deducted from Host Vault balance.",
            'vault_alert',
            '/host/transactions'
        );

        return back()->with('success', '✓ Expense of ₱' . number_format($expense->amount, 2) . ' recorded successfully and deducted from Host Vault balance!');
    }

    public function createCollector()
    {
        return view('admin.encoder.create_collector');
    }

    public function storeCollector(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:users,phone_number',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'address' => 'required|string',
            'assigned_area' => 'required|string',
            'valid_id' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $validIdPath = null;
        if ($request->hasFile('valid_id')) {
            $file = $request->file('valid_id');
            $uploadDir = public_path('uploads/id_proofs');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'col_id_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $filename);
            $validIdPath = 'uploads/id_proofs/' . $filename;
        }

        $user = User::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'pin_code' => '1234',
            'role' => 'collector',
            'address' => $request->address,
            'valid_id_path' => $validIdPath,
            'status' => 'pending', // Pending Host approval
        ]);

        Collector::create([
            'user_id' => $user->id,
            'assigned_area' => $request->assigned_area,
            'commission_balance' => 0.00,
            'total_earned_commission' => 0.00,
        ]);

        SystemNotification::sendNotification(
            null,
            'host',
            'New Collector Account Submitted',
            "Admin Encoder registered new collector {$user->name}. Waiting for Host approval.",
            'approval_needed',
            '/host/approvals'
        );

        return redirect()->route('admin.encoder.dashboard')->with('success', "Collector account for {$user->name} created and submitted to Host for approval!");
    }

    public function printDailyPayments(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $payments = LoanPayment::with(['client.user', 'collector.user', 'loan'])
            ->whereDate('payment_date', $date)
            ->latest()
            ->get();

        return view('admin.encoder.print_daily_payments', compact('payments', 'date'));
    }
}
