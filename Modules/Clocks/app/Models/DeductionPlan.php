<?php

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'overwrite',
        'overwrite_dep',
        'overwrite_subdep',
        'grace_minutes',
        'rules',
    ];

    protected $casts = [
        'overwrite' => 'boolean',
        'overwrite_dep' => 'boolean',
        'overwrite_subdep' => 'boolean',
        'grace_minutes' => 'integer',
        'rules' => 'array',
    ];

    public function planable()
    {
        return $this->morphTo();
    }
}
