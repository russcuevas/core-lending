<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_role',
        'title',
        'message',
        'type',
        'link_url',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function sendNotification(?int $userId, ?string $targetRole, string $title, string $message, string $type = 'info', ?string $linkUrl = null)
    {
        return self::create([
            'user_id' => $userId,
            'target_role' => $targetRole,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link_url' => $linkUrl,
            'is_read' => false,
        ]);
    }
}
