<?php
/*
 * Created At: 2026-04-30T05:26:25Z
 */

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Models\User;

class B2bFixedPermissionSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'day_of_week',
        'position',
        'slot_from',
        'slot_to',
        'is_active',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active slots for a given date.
     *
     * @param Builder $query
     * @param string $date
     * @return Builder
     */
    public function scopeActiveOn(Builder $query, string $date)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $date);
            });
    }
}
