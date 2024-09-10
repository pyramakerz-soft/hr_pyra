<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
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
        $location = $this->route('location'); // Assuming the route parameter is 'location'
        // dd($location);
        return [
            'name' => ['sometimes', 'string'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['sometimes', 'numeric', Rule::unique('locations', 'latitude')->ignore($location->id)],
            'longitude' => ['sometimes', 'numeric', Rule::unique('locations', 'longitude')->ignore($location->id)],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
