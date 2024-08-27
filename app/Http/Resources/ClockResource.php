<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ClockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $clockOut = $this->clock_out ? Carbon::parse($this->clock_out)->format('h:iA') : null;
        if ($this->location_type == "site") {
            if ($this->clock_out) {
                $locationOut = $this->location->address;
            }
        }
        $locationOut = null;
        if ($this->location_type == "site") {
            if ($this->clock_in) {
                $locationIn = $this->location->address;
            }
        }
        $locationIn = null;
        $totalHours = $this->clock_out ? Carbon::parse($this->duration)->format('H:i') : null;

        $allClocks = $this->resource->first()->get();

        $otherClocksForDay = $allClocks->filter(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString() === Carbon::parse($this->clock_in)->toDateString() && $clock->id !== $this->id;
        })->map(function ($clock) {
            return [
                'id' => $clock->id,
                'clockIn' => $clock->clock_in ? Carbon::parse($clock->clock_in)->format('h:iA') : null,
                'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('h:iA') : null,
                'totalHours' => $clock->duration ? Carbon::parse($clock->duration)->format('h:i') : null,

            ];
        })->values()->toArray();

        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'),
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'),
            'clockIn' => Carbon::parse($this->clock_in)->format('h:iA'),
            'clockOut' => $clockOut,
            'totalHours' => $totalHours,
            'locationIn' => $locationIn,
            'locationOut' => $locationOut,
            'userId' => $this->user->id,
            'site' => $this->location_type,
            'formattedClockIn' => Carbon::parse($this->clock_in)->format('Y-m-d H:i'),
            'formattedClockOut' => Carbon::parse($this->clock_out)->format('Y-m-d H:i'),
            'otherClocks' => $otherClocksForDay,
            // 'site' => $this->user->work_types->pluck('name'),

        ];
    }
}