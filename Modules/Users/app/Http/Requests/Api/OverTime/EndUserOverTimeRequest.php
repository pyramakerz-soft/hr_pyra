<?php

namespace Modules\Users\Http\Requests\Api\OverTime;

use Illuminate\Foundation\Http\FormRequest;

class EndUserOverTimeRequest extends FormRequest
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
            'to' => 'required|date',  // Validate 'to' as required and a valid date
            'overtime_id' => 'required|exists:over_time,id',  // Validate that 'overtime_id' exists in the 'over_time' table
        ];
    }
}
