<?php

namespace Modules\Users\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

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


        $today = Carbon::now()->format('Y-m-d'); // Only date, no time
        Log::info($today);  
        // Get the first ClockInOut record where clock_in is not null and date is today
        $clockInRecord = $this->user_clocks()
        ->whereDate('clock_in', $today)
        ->whereNotNull('clock_in')
        ->orderBy('clock_in', 'desc')
        ->first(); // ✅ correct
        Log::info($clockInRecord);
        // Log::info('All Clock Records Today:', $this->user_clocks()->whereDate('clock_in', $today)->get());

        // Format clock-in time or return empty string

    
                    $clockInFormatted =$clockInRecord && $clockInRecord->clock_in
        ? Carbon::parse($clockInRecord->clock_in)->addHours($timezoneValue)->format('h:i A')
        : '--:--';

      
        Log::info($this->subDepartment);
        return [

            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'department' => $this->department != null ? $this->department->name:
                ($this->subDepartment != null ?
                    $this->subDepartment->name :

                    null),
            "position" => $this->user_detail->emp_type ?? null,
            'role' => $this->getRoleName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'working_hours' => $this->user_detail->working_hours_day ?? null,    
                'clock_in_time' => $clockInFormatted, // ✅ Today's clock-in time or empty
                'userTimeZone' => $timezoneValue,  // The timezone value (e.g., +3 or -3)

        ];
    }
}
