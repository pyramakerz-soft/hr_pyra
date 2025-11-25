<?php

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Models\User;

class UserClockOvertime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in_out_id',
        'overtime_date',
        'overtime_minutes',
        'approval_of_direct',
        'approval_of_head',
        'direct_approved_by',
        'head_approved_by',
    ];

    protected $casts = [
        'overtime_date' => 'date',
    ];

    protected $appends = [
        'overtime_hours',
        'overall_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clock()
    {
        return $this->belongsTo(ClockInOut::class, 'clock_in_out_id');
    }

    public function directApprover()
    {
        return $this->belongsTo(User::class, 'direct_approved_by');
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function getOvertimeHoursAttribute(): string
    {
        $hours = intdiv($this->overtime_minutes, 60);
        $minutes = $this->overtime_minutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function getOverallStatusAttribute(): string
    {
        if ($this->approval_of_direct === 'declined' || $this->approval_of_head === 'declined') {
            return 'declined';
        }

        if ($this->approval_of_direct === 'approved' || $this->approval_of_head === 'approved') {
            return 'approved';
        }

        return 'pending';
    }

    public function isDeclined(): bool
    {
        return $this->approval_of_direct === 'declined' || $this->approval_of_head === 'declined';
    }

    public function isFullyApproved(): bool
    {
        return $this->approval_of_direct === 'approved' && $this->approval_of_head === 'approved';
    }
}
