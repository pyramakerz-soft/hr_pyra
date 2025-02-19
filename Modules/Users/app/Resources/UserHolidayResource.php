<?php

namespace Modules\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserHolidayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "date_of_holiday" => $this->date_of_holiday,
            "department_id" => $this->department_id,
            "user" => new UserResource($this->whenLoaded('user')),

        ];
    }
}
