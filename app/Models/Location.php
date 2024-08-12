<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $guarded = [];
    use HasFactory;
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_locations', 'location_id', 'user_id')->withTimestamps();
    }
    public function clocks()
    {
        return $this->hasMany(ClockInOut::class);
    }

}
