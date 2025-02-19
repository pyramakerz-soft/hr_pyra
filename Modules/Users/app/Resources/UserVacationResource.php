<?php

namespace Modules\Users\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserVacationResource extends JsonResource
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
            "user_id" => $this->user_id,
            "sick_left" => $this->sick_left,
            "paid_left" => $this->paid_left,
            "deduction_left" => $this->deduction_left,
        ];
    }
}
