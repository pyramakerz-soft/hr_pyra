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

        $salary = $this->salary ?? null;
        $working_hours_day = $this->working_hours_day;

        if ($working_hours_day === null || $working_hours_day == 0) {
            $hourly_rate = 0;
        } else {
            $hourly_rate = ($salary / 30) / $working_hours_day;
        }
        $start_time = $this->start_time; //07:00
        $end_time = $this->end_time; //15:00
        return [
            "id" => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            "password" => $this->user->password,
            "phone" => $this->user->phone,
            "contact_phone" => $this->user->contact_phone,
            "gender" => $this->user->gender,
            "department_id" => $this->user->department_id,
            "deparment_name" => $this->user->department->name,
            "role" => $this->user->roles->pluck('name'),
            "national_id" => $this->user->national_id,
            "salary" => $salary,
            "working_hours_day" => $working_hours_day,
            "hourly_rate" => $hourly_rate,
            "overtime_hours" => $this->overtime_hours,
            'start_time' => $start_time,
            'code' => $this->user->code,
            'end_time' => $end_time,
            "emp_type" => $this->emp_type,
            "hiring_date" => $this->hiring_date,

        ];
    }
}
