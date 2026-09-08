<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'collector_id',
        'principal_amount',
        'interest_rate_percent',
        'total_payable',
        'daily_installment',
        'term_days',
        'remaining_balance',
        'total_paid',
        'status',
        'release_date',
        'release_note',
        'decline_reason',
        'encoder_id',
        'releasing_officer_id',
        'host_approved_by',
        'host_approved_at',
        'disbursement_proof_path',
        'collector_commission_paid',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }

    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('day_number');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class)->latest();
    }

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoder_id');
    }

    public function releasingOfficer()
    {
        return $this->belongsTo(User::class, 'releasing_officer_id');
    }

    public function hostApprover()
    {
        return $this->belongsTo(User::class, 'host_approved_by');
    }

    public function getDaysPaidCountAttribute()
    {
        return $this->schedules()->where('status', 'paid')->count();
    }

    /**
     * Auto-sync missed past due days, extend loan schedule by adding extra days (e.g. Day 61+),
     * and trigger notifications if 3 or more accumulative unpaid days are reached.
     */
    public function syncMissedDaysAndExtensions()
    {
        if ($this->status !== 'active') {
            return;
        }

        $todayStr = Carbon::today()->toDateString();

        // 1. Find all past due schedules that are not fully paid
        $pastUnpaidSchedules = $this->schedules()
            ->where('due_date', '<', $todayStr)
            ->where('status', '!=', 'paid')
            ->get();

        $missedCount = $pastUnpaidSchedules->count();

        // Update Client's consecutive / accumulative missed days count
        if ($this->client) {
            $this->client->update([
                'consecutive_missed_days' => $missedCount,
            ]);
        }

        // 2. Base term is 60 days. For each missed past due date, extend by 1 extra day.
        $baseTerm = 60;
        $targetTotalDays = $baseTerm + $missedCount;

        $allSchedules = $this->schedules()->orderBy('day_number')->get();
        $currentCount = $allSchedules->count();

        if ($currentCount < $targetTotalDays) {
            $lastSchedule = $allSchedules->last();
            $lastDueDate = $lastSchedule ? Carbon::parse($lastSchedule->due_date) : Carbon::today();

            for ($day = $currentCount + 1; $day <= $targetTotalDays; $day++) {
                $lastDueDate = $lastDueDate->copy()->addDay();
                LoanSchedule::create([
                    'loan_id' => $this->id,
                    'day_number' => $day,
                    'due_date' => $lastDueDate->format('Y-m-d'),
                    'expected_amount' => $this->daily_installment,
                    'paid_amount' => 0.00,
                    'status' => 'unpaid',
                ]);
            }

            $this->update(['term_days' => $targetTotalDays]);
        }

        // 3. Send system notifications if 3 or more accumulative unpaid days
        if ($missedCount >= 3 && $this->client) {
            $todayDate = Carbon::today()->toDateString();

            // Check if client was already notified today for overdue alert to prevent spamming
            $alreadyNotifiedToday = SystemNotification::where('user_id', $this->client->user_id)
                ->where('type', 'overdue_alert')
                ->whereDate('created_at', $todayDate)
                ->exists();

            if (!$alreadyNotifiedToday) {
                $totalOverdueAmount = $missedCount * $this->daily_installment;

                // Notify Client
                SystemNotification::sendNotification(
                    $this->client->user_id,
                    'client',
                    '⚠️ Overdue Notice: ' . $missedCount . ' Missed Payments',
                    "You have accumulated {$missedCount} unpaid daily installments (₱" . number_format($totalOverdueAmount, 2) . "). Extra extension days (Day 61+) have been added to your repayment schedule. Please settle your dues with your assigned collector.",
                    'overdue_alert',
                    '/client/dashboard'
                );

                // Notify Collector
                if ($this->collector && $this->collector->user_id) {
                    SystemNotification::sendNotification(
                        $this->collector->user_id,
                        'collector',
                        '🚨 Delinquent Alert: ' . $this->client->user->name,
                        "Client {$this->client->user->name} has accumulated {$missedCount} unpaid daily installments (₱" . number_format($totalOverdueAmount, 2) . "). Repayment schedule has been extended to Day {$this->term_days}.",
                        'overdue_alert',
                        '/collector/dashboard'
                    );
                }

                // Notify Host / Superadmin
                SystemNotification::sendNotification(
                    null,
                    'host',
                    '🚨 Delinquent Client Alert: ' . $this->client->user->name,
                    "Client {$this->client->user->name} has {$missedCount} accumulative unpaid daily installments. Term extended to Day {$this->term_days}.",
                    'overdue_alert',
                    '/host/transactions'
                );
            }
        }
    }
}
