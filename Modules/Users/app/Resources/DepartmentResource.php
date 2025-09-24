<?php

namespace Modules\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $is_location_time = $this->is_location_time ? true : false;
        return [
            "id" => $this->id,
            "name" => $this->name,
            'is_location_time' => $is_location_time,
            'work_schedule_type' => $this->work_schedule_type ?? 'flexible',
            'works_on_saturday' => (bool) ($this->works_on_saturday ?? false),
            "manager_id" => $this->manager_id,
            'manager_name' => $this->manager ? $this->manager->name : null,
        ];
    }
}
