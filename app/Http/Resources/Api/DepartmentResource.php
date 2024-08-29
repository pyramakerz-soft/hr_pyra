<?php

namespace App\Http\Resources\Api;

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
        // dd($this->manager_id);
        // $users = User::with('roles')->get();
        // foreach ($users as $user) {
        //     foreach ($user->roles as $role) {

        //         // if($role['pivot']['role_id']);
        //     }
        // }

        // foreach ($this->manager as $manager) {
        //     dd($manager);
        // }
        // $departments = Department::with('manager')->get();
        // foreach ($departments as $department) {
        //     dd($department['manager']['name'], $department['manager']['name']);
        // }
        return [
            "id" => $this->id,
            "name" => $this->name,
            "manager_id" => $this->manager_id,
            'manager_name' => $this->manager ? $this->manager->name : null,
        ];
    }
}
