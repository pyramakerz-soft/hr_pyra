<?php

namespace Modules\Users\Resources;

use Carbon\Carbon;
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
        $timezoneValue = $this->timezone ? $this->timezone->value : 3;  // Default to +3 if no timezone

        // Support date filtering
        $from_day = $request->get('from_day');
        $to_day   = $request->get('to_day');
        $clockInRecord = null;
        $clockOutRecord = null;

        if ($from_day && $to_day) {
            // Ensure we cover full day
            $start = Carbon::parse($from_day)->startOfDay();
            $end   = Carbon::parse($to_day)->endOfDay();

            // Filter clocks within range, get the earliest and latest
            $clockInRecord = $this->user_clocks()
                ->whereBetween('clock_in', [$start, $end])
                ->whereNotNull('clock_in')
                ->orderBy('clock_in', 'asc')
                ->first();

            $clockOutRecord = $this->user_clocks()
                ->whereBetween('clock_in', [$start, $end])
                ->whereNotNull('clock_out')
                ->orderBy('clock_out', 'desc')
                ->first();
        } else {
            // Default: just today
            $today = Carbon::now()->format('Y-m-d');
            $start = Carbon::parse($today)->startOfDay();
            $end   = Carbon::parse($today)->endOfDay();

            $clockInRecord = $this->user_clocks()
                ->whereBetween('clock_in', [$start, $end])
                ->whereNotNull('clock_in')
                ->orderBy('clock_in', 'asc')
                ->first();

            $clockOutRecord = $this->user_clocks()
                ->whereBetween('clock_in', [$start, $end])
                ->whereNotNull('clock_out')
                ->orderBy('clock_out', 'desc')
                ->first();
        }

        $clockInFormatted = $clockInRecord && $clockInRecord->clock_in
            ? Carbon::parse($clockInRecord->clock_in)->addHours($timezoneValue)->format('h:i A')
            : '--:--';

        $clockOutFormatted = $clockOutRecord && $clockOutRecord->clock_out
            ? Carbon::parse($clockOutRecord->clock_out)->addHours($timezoneValue)->format('h:i A')
            : '--:--';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'department' => $this->department != null ? $this->department->name :
                ($this->subDepartment != null ?
                    $this->subDepartment->name :
                    null),
            'position' => $this->user_detail->emp_type ?? null,
            'role' => $this->getRoleName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'working_hours' => $this->user_detail->working_hours_day ?? null,
            'clock_in_time' => $clockInFormatted,
            'clock_out_time' => $clockOutFormatted,
            'userTimeZone' => $timezoneValue,
        ];
    }
}
