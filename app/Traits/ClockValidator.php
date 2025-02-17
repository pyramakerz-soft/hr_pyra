<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
trait ClockValidator
{
    protected function validateClockTime($clockIn, $clockOut)
    {
    
        if (!$clockIn->isSameDay($clockOut)) {
            throw ValidationException::withMessages(['error' => 'Clock-out must be on the same day as clock-in.']);
        }
        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            throw ValidationException::withMessages(['error' => "You can't clock out before or at the same time as clock in."]);
        }
    }
}
