<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTurnover extends Model
{
    use HasFactory;

    protected $fillable = [
        'turnover_reference',
        'admin_id',
        'host_id',
        'total_amount',
        'loan_collection_amount',
        'insurance_collection_amount',
        'date',
        'status',
        'admin_notes',
        'host_notes',
        'approved_at',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'turnover_id');
    }
}
