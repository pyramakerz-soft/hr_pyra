<?php

namespace Modules\Users\Models;




use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timezone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'value'];
}
