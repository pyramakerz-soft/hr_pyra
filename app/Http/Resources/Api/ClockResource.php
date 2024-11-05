<?php
namespace App\Http\Resources\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Format clock in time
        $clockIn = $this->clock_in ? Carbon::parse($this->clock_in)->format('h:iA') : null;

        // Calculate clock out time and duration
        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out)->format('h:iA');
            $formattedClockOut = Carbon::parse($this->clock_out)->format('Y-m-d H:i');
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::parse($this->clock_out))->format('%H:%I');
        } else {
            $clockOut = null;
            $formattedClockOut = null;
            $duration = null;
        }

        // Get location for clock in and clock out
        $locationName = $this->location->name;
        $locationIn = $this->location_type === "site" && $this->clock_in ? $this->location->address : null;
        $locationOut = $this->location_type === "site" && $this->clock_out ? $this->location->address : null;

        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'),
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'),
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'locationName' => $locationName,
            'totalHours' => $duration,
            'locationIn' => $locationIn,
            'locationOut' => $locationOut,
            'userId' => $this->user->id,
            'site' => $this->location_type,
            'formattedClockIn' => Carbon::parse($this->clock_in)->format('Y-m-d H:i'),
            'formattedClockOut' => $formattedClockOut,
            'lateArrive' => $this->late_arrive,
            'earlyLeave' => $this->early_leave,
            'is_issue' => $this->is_issue ? true : false,
        ];
    }
}
