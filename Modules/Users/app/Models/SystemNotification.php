<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemNotification extends Model
{
    use HasFactory;

    protected $table = 'system_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'scope_type',
        'scope_id',
        'filters',
        'created_by',
        'scheduled_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(SystemNotificationRecipient::class, 'notification_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

