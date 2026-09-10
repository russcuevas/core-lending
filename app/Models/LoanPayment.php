<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'client_id',
        'collector_id',
        'amount_paid',
        'proof_image_path',
        'client_pin_verified',
        'payment_date',
        'notes',
        'client_remaining_balance_after',
        'status',
        'payment_channel',
        'loan_premium_amount',
        'insurance_premium_amount',
        'remitted_at',
        'admin_pin_verified_by',
        'admin_pin_verified_at',
        'turnover_id',
    ];

    protected $casts = [
        'client_pin_verified' => 'boolean',
        'payment_date' => 'date',
        'remitted_at' => 'datetime',
        'admin_pin_verified_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }

    public function adminVerifier()
    {
        return $this->belongsTo(User::class, 'admin_pin_verified_by');
    }

    public function turnover()
    {
        return $this->belongsTo(CashTurnover::class, 'turnover_id');
    }
}
