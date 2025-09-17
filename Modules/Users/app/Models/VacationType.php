<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'default_days',
    ];

    public function balances()
    {
        return $this->hasMany(UserVacationBalance::class);
    }

    public function vacations()
    {
        return $this->hasMany(UserVacation::class);
    }
}
