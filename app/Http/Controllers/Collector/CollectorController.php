<?php

namespace App\Http\Controllers\Collector;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Collector;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanPayment;
use App\Models\WalletTransaction;
use App\Models\HostVaultLedger;
use App\Models\SystemNotification;
use App\Models\SystemSetting;
use Carbon\Carbon;

class CollectorController extends Controller
{
    public function dashboard()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);

        $assignedClients = Client::with(['user.walletTransactions', 'currentLoan.schedules'])
            ->where('collector_id', $collector->id)
            ->get();

        foreach ($assignedClients as $cl) {
            if ($cl->currentLoan) {
                $cl->currentLoan->syncMissedDaysAndExtensions();
            }
        }

        $activeLoansCount = Loan::where('collector_id', $collector->id)->where('status', 'active')->count();
        $today = Carbon::today();

        $todayCollections = LoanPayment::where('collector_id', $collector->id)
            ->whereDate('payment_date', $today)
            ->with(['client.user', 'loan'])
            ->latest()
            ->get();

        $todayTotalCollected = $todayCollections->sum('amount_paid');
        $todayProcessingAmount = $todayCollections->where('status', 'processing')->sum('amount_paid');
        $todayRemittedAmount = $todayCollections->where('status', 'paid')->sum('amount_paid');
        $pendingRemittanceCount = $todayCollections->where('status', 'processing')->count();

        // Check for 3 consecutive missed payment clients to show warning
        $delinquentClients = Client::where('collector_id', $collector->id)
            ->where('consecutive_missed_days', '>=', 3)
            ->with('user')
            ->get();

        $recentPayments = LoanPayment::where('collector_id', $collector->id)
            ->with(['client.user', 'loan', 'adminVerifier'])
            ->latest()
            ->take(20)
            ->get();

        $remittanceHistory = LoanPayment::where('collector_id', $collector->id)
            ->where('status', 'paid')
            ->whereNotNull('remitted_at')
            ->with(['client.user', 'loan', 'adminVerifier'])
            ->latest('remitted_at')
            ->take(50)
            ->get();

        return view('collector.dashboard', compact(
            'collector',
            'assignedClients',
            'activeLoansCount',
            'todayCollections',
            'todayTotalCollected',
            'todayProcessingAmount',
            'todayRemittedAmount',
            'pendingRemittanceCount',
            'delinquentClients',
            'recentPayments',
            'remittanceHistory'
        ));
    }

    public function scanQr()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);
        $clients = Client::with(['user.walletTransactions', 'currentLoan'])
            ->where('collector_id', $collector->id)
            ->whereHas('currentLoan', function ($q) {
                $q->where('status', 'active');
            })
            ->get();
        return view('collector.scan_qr', compact('clients'));
    }

    public function collectPaymentForm(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);
        $client = null;

        if ($request->filled('qr_token')) {
            $client = Client::where('qr_code_token', $request->qr_token)->with(['user', 'currentLoan.schedules'])->first();
        } elseif ($request->filled('client_id')) {
            $client = Client::where('id', $request->client_id)->with(['user', 'currentLoan.schedules'])->first();
        }

        if (!$client) {
            return redirect()->route('collector.scan_qr')->with('error', 'Client QR code or ID not found.');
        }

        if (!$client->currentLoan || $client->currentLoan->status !== 'active') {
            return redirect()->route('collector.scan_qr')->with('error', 'Client does not currently have an active loan for collection.');
        }

        // Check if client has a pending wallet transaction (Cash-In, Cash-Out) awaiting Host Approval
        $hasPendingWalletTx = $client->user->walletTransactions()
            ->whereIn('status', ['pending_releasing_review', 'pending_host_approval', 'approved_by_host'])
            ->exists();

        if ($hasPendingWalletTx) {
            return redirect()->route('collector.dashboard')->with('error', "Payment collection locked: Client {$client->user->name} has a pending wallet transaction awaiting Host approval.");
        }

        $loan = $client->currentLoan;
        $loan->syncMissedDaysAndExtensions();
        $loan->refresh();

        $paidDays = $loan->schedules()->where('status', 'paid')->count();
        $nextUnpaidSchedule = $loan->schedules()->where('status', '!=', 'paid')->first();
        $insuranceDaily = (float)($loan->insurance_premium_daily > 0 ? $loan->insurance_premium_daily : SystemSetting::get('loan_insurance_premium_daily', 5.00));
        $loanPremiumDaily = (float)($loan->loan_premium_daily > 0 ? $loan->loan_premium_daily : $loan->daily_installment);
        $totalDailyPayable = $loanPremiumDaily + $insuranceDaily;

        return view('collector.collect_payment', compact('client', 'loan', 'paidDays', 'nextUnpaidSchedule', 'insuranceDaily', 'loanPremiumDaily', 'totalDailyPayable'));
    }

    public function processPayment(Request $request, Loan $loan)
    {
        $request->validate([
            'amount_paid' => 'required|numeric|min:1',
            'client_pin' => 'required|digits:4',
            'photo_proof' => 'required|string', // base64
            'notes' => 'nullable|string',
        ]);

        $client = $loan->client;
        $clientUser = $client->user;
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);

        // Guard against processing if client has pending wallet transactions
        $hasPendingWalletTx = $clientUser->walletTransactions()
            ->whereIn('status', ['pending_releasing_review', 'pending_host_approval', 'approved_by_host'])
            ->exists();

        if ($hasPendingWalletTx) {
            return back()->with('error', 'Cannot process payment. Client has a pending wallet transaction awaiting Host approval.');
        }

        // 1. Verify Client PIN code
        $isPinValid = ($clientUser->pin_code === $request->client_pin) || Hash::check($request->client_pin, $clientUser->password);
        if (!$isPinValid) {
            return back()->with('error', 'Invalid Client PIN entered. Payment verification failed.');
        }

        // 2. Save proof photo to public/uploads/payment_proofs
        $photoData = $request->photo_proof;
        $proofFilename = 'proof_pay_' . time() . '_' . Str::random(8) . '.jpg';
        $proofDir = public_path('uploads/payment_proofs');
        if (!file_exists($proofDir)) {
            mkdir($proofDir, 0755, true);
        }
        $proofPath = 'uploads/payment_proofs/' . $proofFilename;
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $data = substr($photoData, strpos($photoData, ',') + 1);
            $data = base64_decode($data);
            file_put_contents(public_path($proofPath), $data);
        }

        $amountPaid = (float)$request->amount_paid;
        $insuranceDaily = (float)($loan->insurance_premium_daily > 0 ? $loan->insurance_premium_daily : SystemSetting::get('loan_insurance_premium_daily', 5.00));
        $insurancePart = min($amountPaid, $insuranceDaily);
        $loanPart = max(0, $amountPaid - $insurancePart);

        // 3. Create Loan Payment in 'processing' status (Awaiting Admin Finance office remittance PIN)
        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'amount_paid' => $amountPaid,
            'loan_premium_amount' => $loanPart,
            'insurance_premium_amount' => $insurancePart,
            'proof_image_path' => $proofPath,
            'client_pin_verified' => true,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'status' => 'processing', // Queued for office remittance
            'payment_channel' => 'cash_collector',
            'notes' => $request->notes,
            'client_remaining_balance_after' => $loan->remaining_balance,
        ]);

        // Send notification to Client that collection was logged and is processing
        SystemNotification::sendNotification(
            $clientUser->id,
            'client',
            "Payment Processing (₱" . number_format($amountPaid, 2) . ")",
            "Payment collected by {$collector->user->name}. Status: Processing (Will be marked PAID once remitted at office).",
            'payment_received',
            '/client/dashboard'
        );

        return redirect()->route('collector.dashboard')->with('success', "Payment of ₱" . number_format($amountPaid, 2) . " logged with photo proof! Status: Processing (Ready for Office Remittance).");
    }

    public function remitCollections(Request $request)
    {
        $request->validate([
            'admin_pin' => 'required|digits:4',
            'payment_ids' => 'nullable|array',
            'payment_ids.*' => 'exists:loan_payments,id',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);

        // Find Admin Finance / Host user by PIN
        $adminUser = \App\Models\User::whereIn('role', ['admin_releasing', 'host'])
            ->where('status', 'active')
            ->get()
            ->first(function ($admin) use ($request) {
                return ($admin->pin_code === $request->admin_pin) || Hash::check($request->admin_pin, $admin->password);
            });

        if (!$adminUser) {
            return back()->with('error', 'Invalid Admin Finance PIN code. Remittance verification failed.');
        }

        // Query processing payments
        $query = LoanPayment::where('collector_id', $collector->id)
            ->where('status', 'processing');

        if ($request->filled('payment_ids')) {
            $query->whereIn('id', $request->payment_ids);
        }

        $pendingPayments = $query->with(['loan.client.user', 'client.user'])->get();

        if ($pendingPayments->isEmpty()) {
            return back()->with('error', 'No pending collections to remit.');
        }

        $totalRemitted = 0.00;

        foreach ($pendingPayments as $payment) {
            $loan = $payment->loan;
            $client = $payment->client;
            $clientUser = $client->user;
            $amountPaid = (float)$payment->amount_paid;
            $totalRemitted += $amountPaid;

            // Apply payment to loan balance & schedules
            $newRemainingBalance = max(0, $loan->remaining_balance - $amountPaid);
            $newTotalPaid = $loan->total_paid + $amountPaid;
            $isFullyPaid = ($newRemainingBalance <= 0);

            $remainingToDistribute = $amountPaid;
            $unpaidSchedules = $loan->schedules()->where('status', '!=', 'paid')->orderBy('day_number', 'asc')->get();

            foreach ($unpaidSchedules as $schedule) {
                if ($remainingToDistribute <= 0 && !$isFullyPaid) break;

                $neededForThisDay = $schedule->expected_amount - $schedule->paid_amount;
                if ($remainingToDistribute >= $neededForThisDay || $isFullyPaid) {
                    $actualApplied = min($remainingToDistribute, $neededForThisDay);
                    $schedule->update([
                        'paid_amount' => $schedule->expected_amount,
                        'status' => 'paid',
                        'paid_at' => Carbon::now(),
                    ]);
                    $remainingToDistribute = max(0, $remainingToDistribute - $actualApplied);
                } else {
                    $schedule->update([
                        'paid_amount' => $schedule->paid_amount + $remainingToDistribute,
                        'status' => 'partial',
                        'paid_at' => Carbon::now(),
                    ]);
                    $remainingToDistribute = 0;
                }
            }

            if ($isFullyPaid) {
                $loan->schedules()->where('status', '!=', 'paid')->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);
            }

            $loan->update([
                'remaining_balance' => $newRemainingBalance,
                'total_paid' => $newTotalPaid,
                'status' => $isFullyPaid ? 'fully_paid' : 'active',
            ]);

            // Update payment to 'paid' & stamped with Admin Finance PIN verification
            $payment->update([
                'status' => 'paid',
                'remitted_at' => Carbon::now(),
                'admin_pin_verified_by' => $adminUser->id,
                'admin_pin_verified_at' => Carbon::now(),
                'client_remaining_balance_after' => $newRemainingBalance,
            ]);

            // Update client last payment date
            $client->update([
                'last_payment_date' => Carbon::today()->format('Y-m-d'),
                'consecutive_missed_days' => 0,
                'status' => $isFullyPaid ? 'completed' : 'active',
            ]);

            // Host Vault Inflow Ledger
            HostVaultLedger::logEntry(
                'in',
                'loan_repayment',
                $amountPaid,
                "Daily loan repayment verified & remitted for {$clientUser->name} (Collector: {$collector->user->name}, Verified by Finance: {$adminUser->name})",
                'LoanPayment',
                $payment->id,
                $adminUser->id
            );

            // Commission rule for collector if fully paid
            if ($isFullyPaid && !$loan->collector_commission_paid) {
                $bonusComm = (float)SystemSetting::get('collector_loan_commission_fixed', 300.00);
                if ($bonusComm > 0) {
                    $collector->increment('commission_balance', $bonusComm);
                    $collector->increment('total_earned_commission', $bonusComm);
                }
                $loan->update(['collector_commission_paid' => true]);

                SystemNotification::sendNotification(
                    $collector->user_id,
                    'collector',
                    "₱" . number_format($bonusComm, 2) . " Fully-Paid Loan Commission Earned!",
                    "Congratulations! Client {$clientUser->name} has fully paid their loan. ₱" . number_format($bonusComm, 2) . " commission credited to your balance.",
                    'payment_received'
                );
            }

            // Client notification: Paid status confirmed
            SystemNotification::sendNotification(
                $clientUser->id,
                'client',
                "Payment Confirmed & Verified (PAID)",
                "Your daily payment of ₱" . number_format($amountPaid, 2) . " has been received at the office and officially verified as PAID. Remaining Balance: ₱" . number_format($newRemainingBalance, 2),
                'payment_received',
                '/client/dashboard'
            );
        }

        return back()->with('success', "✓ Successfully remitted ₱" . number_format($totalRemitted, 2) . " (" . $pendingPayments->count() . " collections) verified by Admin Finance ({$adminUser->name})! Client statuses updated to PAID.");
    }

    public function requestCashout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector;
        $amount = (float)$request->amount;

        if ($collector->commission_balance < $amount) {
            return back()->with('error', 'Insufficient commission balance for this cashout request.');
        }

        WalletTransaction::create([
            'user_id' => Auth::id(),
            'type' => 'collector_cashout',
            'amount' => $amount,
            'status' => 'pending_releasing_review',
            'releasing_notes' => "Collector commission payout request for {$collector->user->name}.",
        ]);

        SystemNotification::sendNotification(
            null,
            'admin_releasing',
            'Collector Commission Cashout Request',
            "Collector {$collector->user->name} requested commission encashment of ₱" . number_format($amount, 2),
            'request_alert',
            '/admin/releasing/dashboard'
        );

        return back()->with('success', 'Cashout request submitted to Releasing Officer!');
    }
}
