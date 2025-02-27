<?php

namespace Modules\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Users\Enums\StatusEnum;

class Excuse extends Model
{
 protected $table = 'excuses';


 protected $fillable = [
     'date', 'from', 'to', 'user_id',
 ];

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
 
}
