<?php

namespace Modules\Users\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomVacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_full_day',
        'description',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_full_day' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'custom_vacation_department')->withTimestamps();
    }

    public function subDepartments()
    {
        return $this->belongsToMany(SubDepartment::class, 'custom_vacation_sub_department')->withTimestamps();
    }

    public function scopeBetweenDates($query, Carbon $start, Carbon $end)
    {
        return $query->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start);
    }

    public function appliesToUser(User $user): bool
    {
        if (! $user->department_id && ! $user->sub_department_id) {
            return false;
        }

        if ($user->sub_department_id && $this->subDepartments->contains('id', $user->sub_department_id)) {
            return true;
        }

        if ($user->department_id && $this->departments->contains('id', $user->department_id)) {
            return true;
        }

        return false;
    }
}

