<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\StatusEnum;

class UserVacation extends Model
{
    protected $guarded = [];
    use HasFactory;

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}