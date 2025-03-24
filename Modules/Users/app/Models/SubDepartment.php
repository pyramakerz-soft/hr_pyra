<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDepartment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department_id', 'teamlead_id'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'teamlead_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'users')
            ->where('users.id', '!=', $this->manager_id);
    }
}
