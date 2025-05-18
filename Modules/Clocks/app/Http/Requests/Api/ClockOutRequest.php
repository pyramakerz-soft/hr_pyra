<?php

namespace Modules\Clocks\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\Clocks\Models\ClockInOut;

class ClockOutRequest extends FormRequest
{
    protected $lastClockIn;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    protected function prepareForValidation()
    {
        // Retrieve the authenticated user's last clock-in without clock-out
        $user = Auth::user();

        $this->lastClockIn = ClockInOut::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->where('location_type', 'site') // Checking only for 'site' type
            ->orderBy('clock_in', 'desc') // Getting the latest clock-in
            ->first();

        // Merge location_type into the request to allow conditional validation
        if ($this->lastClockIn) {
            $this->merge([
                'location_type' => $this->lastClockIn->location_type,
            ]);
        }
    }
    public function rules(): array
    {

        return [
            // 'clock_out' => ['required', 'date_format:Y-m-d H:i:s'],
            'latitude' => 'required_if:location_type,site|numeric|between:-90,90',
            'longitude' => 'required_if:location_type,site|numeric|between:-180,180',
        ];
    }
}
