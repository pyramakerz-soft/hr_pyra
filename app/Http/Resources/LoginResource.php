<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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
            'name' => $this->name,
            'national_id' => $this->national_id,

            'image' => $this->image ?? null,
            'job_title' => $this->user_detail->emp_type ?? null,
            'role_name' => $this->getRoleName(),
        ];

    }
}
