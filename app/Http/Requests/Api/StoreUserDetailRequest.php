<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserDetailRequest extends FormRequest
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
            'salary' => ['required', 'numeric'],
            'working_hours_day' => ['required', 'numeric', 'min:4'],
            'overtime_hours' => ['required', 'numeric'],
            'emp_type' => ['required', 'string'],
            'hiring_date' => ['required', 'date'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
