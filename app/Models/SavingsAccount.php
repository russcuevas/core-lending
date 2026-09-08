<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'collector_id',
        'deposit_amount',
        'interest_rate_percent',
        'lock_in_days',
        'daily_interest_amount',
        'total_expected_interest',
        'accumulated_interest_paid',
        'days_credited',
        'start_date',
        'maturity_date',
        'status',
        'collector_commission_amount',
        'collector_commission_credited',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }
}
