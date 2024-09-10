<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClockInOutRequest extends FormRequest
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
            //     'clock_in' => ['nullable', 'date_format:Y-m-d H:i'],
            //     'clock_out' => ['nullable', 'date_format:Y-m-d H:i'],
            //     'latitude' => 'required_if:location_type,==,site|numeric',
            //     'longitude' => 'required_if:location_type,==,site|numeric',
        ];
    }
}
