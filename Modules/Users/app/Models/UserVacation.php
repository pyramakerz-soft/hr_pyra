<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\StatusEnum;

class UserVacation extends Model
{
    protected $guarded = [];
    use HasFactory;

    protected $casts = [
        'status' => StatusEnum::class,
        'approval_of_direct' => StatusEnum::class,
        'approval_of_head' => StatusEnum::class,
        'from_date' => 'datetime',
        'to_date' => 'datetime',
    ];

    protected $appends = [
        'overall_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vacationType()
    {
        return $this->belongsTo(VacationType::class, 'vacation_type_id');
    }

    public function directApprover()
    {
        return $this->belongsTo(User::class, 'direct_approved_by');
    }

    public function headApprover()
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function getOverallStatusAttribute(): string
    {
        $direct = $this->extractStatusValue($this->approval_of_direct);
        $head = $this->extractStatusValue($this->approval_of_head);

        if ($direct === 'declined' || $head === 'declined') {
            return 'declined';
        }

        if ($direct === 'approved' && $head === 'approved') {
            return 'approved';
        }

        return 'pending';
    }

    protected function extractStatusValue($status): ?string
    {
        if ($status instanceof StatusEnum) {
            return $status->value;
        }

        return $status ?: null;
    }

}