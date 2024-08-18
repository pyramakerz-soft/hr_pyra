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
        } else {
            $clock_out = Carbon::parse($this->clock_out)->format('H:iA');
        }
        return [
            'id' => $this->id,
            'Day' => Carbon::parse($this->clock_in)->format('l'), // Extracts the day (e.g., "Sunday")
            'Date' => Carbon::parse($this->clock_in)->format('Y-m-d'), // Extracts the date (e.g., "2024-08-18")
            'Clock In' => Carbon::parse($this->clock_in)->format('H:iA'), // Extracts the time (e.g., "09:20:21")
            'Clock Out' => $clock_out,
            'Total Hours' => Carbon::parse($this->duration)->format('H:i') ?? null,
            'Location In' => $this->location->address,
            'Location Out' => $this->location->address,
            'site' => $this->user->work_types->pluck('name'),
        ];
    }
}