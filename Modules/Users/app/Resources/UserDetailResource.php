<?php

namespace Modules\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

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
        $work_home = false;
        $locationTypes = $this->user->work_types->pluck('name');
        if (count($locationTypes) > 1) {
            $work_home = true;
        }
        Log::info('/////////////////');

        Log::info(json_encode( $this->user->roles()->first(),
));

        return [
            "id" => $this->user->id,
            'name' => $this->user->name,
            'image' => $this->user->image,
            'email' => $this->user->email,
            "phone" => $this->user->phone,
            "contact_phone" => $this->user->contact_phone,
            "gender" => $this->user->gender,
            "department_id" => $this->user->department_id  ?? null,
            "sub_department_id" => $this->user->sub_department_id  ?? null,
            "timezone_id" => $this->user->timezone_id ?? null,

            "deparment_name" => $this->user->department->name ?? null,
            "role" => $this->user->roles->first(),
            "national_id" => $this->user->national_id,
            "salary" => $salary,
            "working_hours_day" => $working_hours_day,
            "hourly_rate" => $hourly_rate,
            "overtime_hours" => $this->overtime_hours,
            // 'is_float' => $this->is_float,

            'start_time' => $start_time,
            'code' => $this->user->code,
            'end_time' => $end_time,
            "emp_type" => $this->emp_type,
            "hiring_date" => $this->hiring_date,
            'location_id' => $this->user->user_locations->pluck('id'),
            'location' => $this->user->user_locations->pluck('name'),
            'work_type_id' => $this->user->work_types->pluck('id'),
            'work_type_name' => $this->user->work_types->pluck('name'),
            'work_home' => $work_home,
        ];
    }
}
