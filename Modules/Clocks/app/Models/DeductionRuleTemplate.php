<?php

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionRuleTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'category',
        'scope',
        'description',
        'rule',
        'is_active',
    ];

    protected $casts = [
        'rule' => 'array',
        'is_active' => 'boolean',
    ];
}
