<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authUser = Auth::user();
        $user_clock = $authUser->user_clocks->whereNull('clock_out')->last();
        $is_clocked_out = false;
        if (!$user_clock) {
            $is_clocked_out = true;
        }
        $user_clockIn = $authUser->user_clocks->last();
        if ($user_clockIn) {
            $clockIn = Carbon::parse($user_clockIn->clock_in)->format('H:i:s');
        }
        $work_home = false;
        $locationTypes = $authUser->work_types->pluck('name');
        if (count($locationTypes) > 1) {
            $work_home = true;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'national_id' => $this->national_id,
            'image' => $this->image ?? null,
            'job_title' => $this->user_detail->emp_type ?? null,
            'role_name' => $this->getRoleName(),
            'is_clocked_out' => $is_clocked_out,
            'clockIn' => $clockIn,
            'work_home' => $work_home,
        ];

    }
}