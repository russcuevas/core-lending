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
        $totalCollectedToday = LoanPayment::where('collector_id', $collector->id)
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount_paid');

        // Check for 3 consecutive missed payment clients to show warning
        $delinquentClients = Client::where('collector_id', $collector->id)
            ->where('consecutive_missed_days', '>=', 3)
            ->with('user')
            ->get();

        $recentPayments = LoanPayment::where('collector_id', $collector->id)
            ->with(['client.user', 'loan'])
            ->latest()
            ->take(10)
            ->get();

        return view('collector.dashboard', compact(
            'collector',
            'assignedClients',
            'activeLoansCount',
            'totalCollectedToday',
            'delinquentClients',
            'recentPayments'
        ));
    }

    public function scanQr()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $collector = $user->collector ?? Collector::firstOrCreate(['user_id' => $user->id]);
        $clients = Client::with(['user.walletTransactions', 'currentLoan'])
            ->where('collector_id', $collector->id)
            ->whereHas('currentLoan', function($q) {
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

        return view('collector.collect_payment', compact('client', 'loan', 'paidDays', 'nextUnpaidSchedule'));
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
        $proofPath = 'uploads/payment_proofs/' . $proofFilename;
        if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $type)) {
            $data = substr($photoData, strpos($photoData, ',') + 1);
            $data = base64_decode($data);
            file_put_contents(public_path($proofPath), $data);
        }

        $amountPaid = (float)$request->amount_paid;

        // 3. Update Loan Balance and Schedules
        $newRemainingBalance = max(0, $loan->remaining_balance - $amountPaid);
        $newTotalPaid = $loan->total_paid + $amountPaid;

        // Apply payment across schedules
        // Check if loan is now fully paid
        $isFullyPaid = ($newRemainingBalance <= 0);

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
                // Partial payment - carry over rest
                $schedule->update([
                    'paid_amount' => $schedule->paid_amount + $remainingToDistribute,
                    'status' => 'partial',
                    'paid_at' => Carbon::now(),
                ]);
                $remainingToDistribute = 0;
            }
        }

        // If loan is fully settled, ensure all schedules are marked as paid
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

        // Record payment
        $payment = LoanPayment::create([
            'loan_id' => $loan->id,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'amount_paid' => $amountPaid,
            'proof_image_path' => $proofPath,
            'client_pin_verified' => true,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'notes' => $request->notes,
            'client_remaining_balance_after' => $newRemainingBalance,
        ]);

        // Update Client last payment date & reset missed days
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
            "Daily loan repayment collected from {$clientUser->name} by {$collector->user->name}",
            'LoanPayment',
            $payment->id,
            Auth::id()
        );

        // 4. Commission Rule: If fully paid, collector receives dynamic bonus commission!
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

        return redirect()->route('collector.dashboard')->with('success', "Payment of ₱" . number_format($amountPaid, 2) . " successfully posted with photo proof!");
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
