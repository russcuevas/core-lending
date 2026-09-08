<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collector_id',
        'qr_code_token',
        'qr_code_path',
        'wallet_balance',
        'current_loan_id',
        'status',
        'last_payment_date',
        'consecutive_missed_days',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function currentLoan()
    {
        return $this->belongsTo(Loan::class, 'current_loan_id');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class);
    }

    public function savings()
    {
        return $this->hasMany(SavingsAccount::class);
    }

    public function updateRequests()
    {
        return $this->hasMany(ClientUpdateRequest::class);
    }
}
