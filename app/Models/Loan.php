<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
