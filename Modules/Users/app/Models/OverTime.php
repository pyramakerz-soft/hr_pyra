<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\StatusEnum;

class OverTime extends Model
{
  protected $table = 'over_time';

  protected $guarded = [];



  public $timestamps = true;

  // Cast the 'status' attribute to the OverTimeStatus enum
  protected $casts = [
    'status' => StatusEnum::class,
  ];

  /**
   * Get the user that owns the excuse.
   */
  public function user()
  {
    return $this->belongsTo(User::class);  // Each excuse belongs to one user
  }

  /**
   * Get the direct manager who approved the overtime.
   */
  public function directApprovedBy()
  {
    return $this->belongsTo(User::class, 'direct_approved_by');
  }

  /**
   * Get the head manager who approved the overtime.
   */
  public function headApprovedBy()
  {
    return $this->belongsTo(User::class, 'head_approved_by');
  }

}
