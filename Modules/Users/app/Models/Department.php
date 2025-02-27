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
