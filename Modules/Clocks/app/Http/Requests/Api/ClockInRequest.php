<?php

namespace Modules\Clocks\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
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
    public function rules(): array
    {
        return [
            'location_id'   => ['required_if:location_type,site', 'nullable', 'exists:locations,id'],
            'location_type' => 'required|exists:work_types,name',
            // 'clock_in'      => 'required|date_format:Y-m-d H:i:s',
            'latitude'      => 'required_if:location_type,site|numeric|between:-90,90',
            'longitude'     => 'required_if:location_type,site|numeric|between:-180,180',
        ];
    }
    
}
