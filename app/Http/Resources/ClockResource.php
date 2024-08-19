<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ClockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->clock_out == null) {
            $clock_out = null;
            $LocationOut = null;
            $totalHours = null;
        } else {
            $clock_out = Carbon::parse($this->clock_out)->format('H:iA');
            $LocationOut = $this->location->address;
            $totalHours = Carbon::parse($this->duration)->format('H:i');
        }
        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'), // Extracts the day (e.g., "Sunday")
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'), // Extracts the date (e.g., "2024-08-18")
            'clockIn' => Carbon::parse($this->clock_in)->format('H:iA'), // Extracts the time (e.g., "09:20:21")
            'clockOut' => $clock_out,
            'totalHours' => $totalHours,

            'locationIn' => $this->location->address,
            'locationOut' => $LocationOut,
            'userId' => $this->user->id,
            'site' => $this->user->work_types->pluck('name'),
        ];
    }
}
