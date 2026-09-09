<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientUpdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'requested_by',
        'old_data',
        'new_data',
        'status',
        'is_read',
        'host_approved_by',
        'host_approved_at',
        'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'is_read' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function hostApprover()
    {
        return $this->belongsTo(User::class, 'host_approved_by');
    }
}
