<?php

namespace Modules\Clocks\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Models\User;

class ServiceAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_type',
        'scope_type',
        'scope_id',
        'payload',
        'result',
        'status',
        'performed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}

