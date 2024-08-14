<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkType extends Model
{
    protected $guarded = [];
    public function users()
    {
        return $this->belongsToMany(WorkType::class, 'user_work_type', 'work_type_id', 'user_id')->withTimestamps();
    }
    use HasFactory;
}
