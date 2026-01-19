<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Modules\Clocks\Models\DeductionPlan;

class SubDepartment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department_id', 'teamlead_id', 'flexible_start_time', 'works_on_saturday'];

    protected $casts = [
        'flexible_start_time' => 'string',
        'works_on_saturday' => 'boolean',
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
        return $this->hasMany(User::class);
    }

    public function customVacations()
    {
        return $this->belongsToMany(CustomVacation::class, 'custom_vacation_sub_department')->withTimestamps();
    }

    public function employees()
    {
        return $this->hasMany(User::class)
            ->where('id', '!=', $this->teamlead_id);
    }
}
