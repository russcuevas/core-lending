<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'status',
        'releasing_officer_id',
        'releasing_notes',
        'releasing_scheduled_date',
        'host_approved_by',
        'host_approved_at',
        'host_notes',
        'decline_reason',
        'proof_image_path',
        'pin_verified',
        'user_balance_after',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function releasingOfficer()
    {
        return $this->belongsTo(User::class, 'releasing_officer_id');
    }

    public function hostApprover()
    {
        return $this->belongsTo(User::class, 'host_approved_by');
    }
}
