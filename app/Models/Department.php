<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    // A department has one manager
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // A department has many users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function user_holidays()
    {
        return $this->hasMany(UserHoliday::class);
    }

}
