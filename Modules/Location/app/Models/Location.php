<?php

namespace Modules\Location\Models;

use App\Models\ClockInOut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Models\User;

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
