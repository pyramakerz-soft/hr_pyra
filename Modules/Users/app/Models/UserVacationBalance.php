<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserVacationBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vacation_type_id',
        'year',
        'allocated_days',
        'used_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'float',
        'used_days' => 'float',
    ];

    protected $appends = [
        'remaining_days',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(VacationType::class, 'vacation_type_id');
    }

    public function vacationType()
    {
        return $this->belongsTo(VacationType::class, 'vacation_type_id');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function getRemainingDaysAttribute(): float
    {
        return max(0, ($this->allocated_days ?? 0) - ($this->used_days ?? 0));
    }
}
