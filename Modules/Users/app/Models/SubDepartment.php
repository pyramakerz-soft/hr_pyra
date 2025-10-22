<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Modules\Clocks\Models\DeductionPlan;

class SubDepartment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department_id', 'teamlead_id', 'flexible_start_time'];

    protected $casts = [
        'flexible_start_time' => 'string',
    ];

    public function deductionPlan()
    {
        return $this->morphOne(DeductionPlan::class, 'planable');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'teamlead_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users');
    }

    public function customVacations()
    {
        return $this->belongsToMany(CustomVacation::class, 'custom_vacation_sub_department')->withTimestamps();
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'users')
            ->where('users.id', '!=', $this->manager_id);
    }
}
