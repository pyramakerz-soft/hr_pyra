<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserDetailRequest extends FormRequest
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
            'salary' => ['nullable', 'numeric'],
            'working_hours_day' => ['nullable', 'numeric', 'min:4'],
            'overtime_hours' => ['nullable', 'numeric'],
            'emp_type' => ['nullable', 'string'],
            'hiring_date' => ['nullable', 'date'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
