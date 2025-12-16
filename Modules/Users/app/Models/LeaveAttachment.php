<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;

class LeaveAttachment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['file_url'];

    public function userVacation()
    {
        return $this->belongsTo(UserVacation::class);
    }

    public function getFileUrlAttribute()
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}
