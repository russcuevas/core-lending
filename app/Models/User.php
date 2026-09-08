<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'pin_code',
        'role',
        'address',
        'valid_id_path',
        'status',
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isAdminEncoder(): bool
    {
        return $this->role === 'admin_encoder';
    }

    public function isAdminReleasing(): bool
    {
        return $this->role === 'admin_releasing';
    }

    public function isCollector(): bool
    {
        return $this->role === 'collector';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function collector()
    {
        return $this->hasOne(Collector::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(SystemNotification::class);
    }
}
