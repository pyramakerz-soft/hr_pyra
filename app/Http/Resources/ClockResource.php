<?php
namespace App\Http\Resources;

use App\Models\ClockInOut;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ClockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $clockIn = $this->clock_in ? Carbon::parse($this->clock_in)->format('h:iA') : null;

        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out)->format('h:iA');
            $formattedClockOut = Carbon::parse($this->clock_out)->format('Y-m-d H:i');
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::parse($this->clock_out))->format('%H:%I');
        } else {
            $clockOut = null;
            $formattedClockOut = null;
            $duration = Carbon::parse($this->clock_in)->diff(Carbon::now())->format('%H:%I');
        }

        $locationIn = $this->location_type === "site" && $this->clock_in ? $this->location->address : null;
        $locationOut = $this->location_type === "site" && $this->clock_out ? $this->location->address : null;

        $allClocks = ClockInOut::where('user_id', $this->user_id)->get();
        $otherClocksForDay = $allClocks->filter(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString() === Carbon::parse($this->clock_in)->toDateString() && $clock->id !== $this->id;
        })->map(function ($clock) {
            return [
                'id' => $clock->id,
                'clockIn' => $clock->clock_in ? Carbon::parse($clock->clock_in)->format('H:iA') : null,
                'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('H:iA') : null,
                'totalHours' => $clock->duration ? Carbon::parse($clock->duration)->format('H:i') : null,
                'site' => $clock->location_type,
                'formattedClockIn' => $clock->clock_in ? Carbon::parse($clock->clock_in)->format('Y-m-d H:i') : null,
                'formattedClockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('Y-m-d H:i') : null,
            ];
        })->values()->toArray();

        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'),
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'),
            'clockIn' => $clockIn,
            'clockOut' => $clockOut,
            'totalHours' => $duration,
            'locationIn' => $locationIn,
            'locationOut' => $locationOut,
            'userId' => $this->user->id,
            'site' => $this->location_type,
            'formattedClockIn' => Carbon::parse($this->clock_in)->format('Y-m-d H:i'),
            'formattedClockOut' => $formattedClockOut,
            'otherClocks' => $otherClocksForDay,
        ];
    }
}
