<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClockInOutRequest extends FormRequest
{
    public function authorize()
    {
        // Optionally, add authorization logic if needed
        return true;
    }

    public function rules()
    {
        return [
            //     'location_type' => 'required|string|exists:work_types,name',
            //     'clock_in' => ['required', 'date_format:Y-m-d H:i:s'],
            //     'latitude' => 'required_if:location_type,==,site|numeric',
            //     'longitude' => 'required_if:location_type,==,site|numeric',
        ];
    }

}
