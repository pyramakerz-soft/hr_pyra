<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotificationRecipient extends Model
{
    use HasFactory;

    protected $table = 'system_notification_recipients';

    protected $fillable = [
        'notification_id',
        'user_id',
        'status',
        'read_at',
        'meta',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'meta' => 'array',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(SystemNotification::class, 'notification_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

