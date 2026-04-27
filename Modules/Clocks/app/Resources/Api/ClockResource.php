<?php

namespace Modules\Clocks\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ClockResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $timezoneOffset = $this->user->timezone ? $this->user->timezone->value : 0;

        // Format clock in time using manual offset from UTC
        $clockIn = $this->clock_in ? Carbon::parse($this->clock_in, 'UTC')->addHours($timezoneOffset)->format('h:iA') : null;

        // Calculate clock out time and duration
        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out, 'UTC')->addHours($timezoneOffset)->format('h:iA');
            $formattedClockOut = Carbon::parse($this->clock_out, 'UTC')->addHours($timezoneOffset)->format('Y-m-d H:i');
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::parse($this->clock_out))->format('%H:%I');
        } else {
            $clockOut = null;
            $formattedClockOut = null;
            $duration = null;
        }

        // Get location for clock in and clock out
        $locationName = $this->location->name ?? null;
        $locationIn = null;
        $locationOut = null;

        if ($this->location_type === 'site') {
            $locationIn = ($this->clock_in && $this->location) ? $this->location->address : null;
            $locationOut = ($this->clock_out && $this->location)  ? $this->location->address : null;
        } elseif ($this->location_type === 'float') {
            $locationIn = $this->clock_in ? $this->address_clock_in : null;
            $locationOut = $this->clock_out ? $this->address_clock_out : null;
        }

        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'),
            'Date' =>  Carbon::parse($this->clock_in, 'UTC')->addHours($timezoneOffset)->format('Y-m-d'),
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'locationName' => $locationName,
            'totalHours' => $duration,
            'locationIn' => $locationIn,
            'locationOut' => $locationOut,
            'userId' => $this->user->id,
            'site' => $this->location_type,
            'formattedClockIn' => Carbon::parse($this->clock_in, 'UTC')->addHours($timezoneOffset)->format('Y-m-d H:i'),
            'formattedClockOut' => $formattedClockOut,
            'lateArrive' => $this->late_arrive,
            'earlyLeave' => $this->early_leave,
            'is_issue' => $this->is_issue ? true : false,
            'userName' => $this->user->name,
            'userCode' => $this->user->code,

        ];
    }
}
