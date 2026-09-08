<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'day_number',
        'due_date',
        'expected_amount',
        'paid_amount',
        'status',
        'paid_at',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
