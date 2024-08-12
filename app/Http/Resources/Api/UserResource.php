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
            'department_id' => $this->department_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_phone' => $this->contact_phone,
            'image' => $this->image,
            'gender' => $gender,
            'userDetail' => new UserDetailResource($this->whenLoaded('user_detail')),
            'userVacations' => UserVacationResource::collection($this->whenLoaded('user_vacations')),
            'Department' => new DepartmentResource($this->whenLoaded('department')),
            'Role' => RoleResource::collection($this->whenLoaded('roles')),

        ];
    }
}
