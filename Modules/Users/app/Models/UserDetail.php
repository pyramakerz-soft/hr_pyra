<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $guarded = [];

    protected $casts = [
        'works_on_saturday' => 'boolean',
    ];

    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
