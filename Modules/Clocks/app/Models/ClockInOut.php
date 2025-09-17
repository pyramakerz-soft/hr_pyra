<?php

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Location\Models\Location;
use Modules\Users\Models\User;

class ClockInOut extends Model
{
    protected $guarded = [];
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function overtimeEntry()
    {
        return $this->hasOne(UserClockOvertime::class, 'clock_in_out_id');
    }

}
