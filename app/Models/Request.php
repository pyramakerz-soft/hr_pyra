<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    public function types()
    {
        return $this->hasOne(Type::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
