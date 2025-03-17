<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    //Constant
    // public const Academic_school = 'ACADEMIC_SCHOOL';
    // public const Factory = 'FACTORY';
    protected $guarded = [];


    // In the Department model
    public function managers()
    {
        return $this->belongsToMany(User::class, 'department_managers', 'department_id', 'manager_id');
    }





    /**
     * A department can have many users.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    /**
     * A department can have many vacations associated with users.
     */
    public function user_vacations()
    {
        return $this->hasMany(UserVacation::class);
    }
}
