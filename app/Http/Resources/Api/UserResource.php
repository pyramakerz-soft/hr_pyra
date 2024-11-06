<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {

        if ($this->gender === 'm' || $this->gender === 'M') {
            $gender = "Male";
        } else if ($this->gender === 'f' || $this->gender === 'F') {
            $gender = "Female";
        }
        return [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'department' => $this->department->name ?? null,
            "position" => $this->user_detail->emp_type ?? null,
            'role' => $this->getRoleName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'working_hours' => $this->user_detail->working_hours_day ?? null,
        ];
    }
}
