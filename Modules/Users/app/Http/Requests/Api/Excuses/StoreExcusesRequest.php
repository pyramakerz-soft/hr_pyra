<?php

namespace Modules\Users\Http\Requests\Api\Excuses;

use Illuminate\Foundation\Http\FormRequest;

class StoreExcusesRequest extends FormRequest
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
    public function rules()
    {
        return [
            'date' => 'required|date', // Validate date format
            'from' => 'required|date_format:H:i',  // Validate time format
            'to' => 'required|date_format:H:i',   // Validate time format

            'status' => 'nullable|in:pending,approved,rejected', // Optional status field
        ];
    }

}
