<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Modules\Clocks\Models\DeductionPlan;

class Department extends Model
{
    use HasFactory;
  
    protected $guarded = [];



    public function deductionPlan()
    {
        return $this->morphOne(DeductionPlan::class, 'planable');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subDepartments()
    {
        return $this->hasMany(SubDepartment::class);
    }

    public function employees()
    {
        return User::where(function ($query) {
            $query->where('department_id', $this->id) // Users in the department
                ->orWhereIn('sub_department_id', $this->subDepartments()->pluck('id')); // Users in sub-departments
        })->where('id', '!=', $this->manager_id); // Exclude department manager
    }


    /**
     * A department can have many users.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
