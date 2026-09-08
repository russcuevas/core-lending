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
}
