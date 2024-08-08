<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salary = $this->salary;
        $working_hours_day = $this->working_hours_day;
        $hourly_rate = ($salary / 30) / $working_hours_day;

        return [
            "id" => $this->id,
            "user_id" => $this->user_id,
            "salary" => $salary,
            "working_hours_day" => $working_hours_day,
            "hourly_rate" => $hourly_rate,
            "overtime_hours" => $this->overtime_hours,
            "emp_type" => $this->emp_type,
            "hiring_date" => $this->hiring_date,

        ];
    }
}
