<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'Clock In' => $this->clock_in,
            'Clock Out' => $this->clock_out,
            'Total Hours' => $this->duration,
            'Location In' => $this->location->address,
            'Location Out' => $this->location->address,
            'site' => 'null',

        ];
    }
}
