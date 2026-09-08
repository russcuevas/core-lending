<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HostVaultLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'category',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'vault_balance_after',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function logEntry(string $type, string $category, float $amount, ?string $description = null, ?string $refType = null, ?int $refId = null, ?int $userId = null)
    {
        $lastEntry = self::latest('id')->first();
        $currentBalance = $lastEntry ? (float)$lastEntry->vault_balance_after : 1000000.00; // Default company capital base 1M

        $newBalance = ($type === 'in') ? ($currentBalance + $amount) : ($currentBalance - $amount);

        return self::create([
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
            'vault_balance_after' => $newBalance,
            'created_by' => $userId ?? Auth::id(),
        ]);
    }
}
