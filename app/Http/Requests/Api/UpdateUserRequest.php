<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'min:3'],
            'email' => ['nullable', 'email'],
            'password' => ['nullable', 'min:6'],
            'phone' => ['nullable', 'regex:/^01[0125][0-9]{8}$/'],
            'contact_phone' => ['nullable', 'regex:/^01[0125][0-9]{8}$/'],
            'national_id' => ['nullable', 'string', 'regex:/^[0-9]{14}$/'],
            'code' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:m,M,F,f'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'image' => ['nullable'],
            'salary' => ['nullable', 'numeric'],
            'working_hours_day' => ['nullable', 'numeric', 'min:4'],
            'overtime_hours' => ['nullable', 'numeric'],
            'emp_type' => ['nullable', 'string'],
            'hiring_date' => ['nullable', 'date'],
            'user_id' => ['exists:users,id'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'work_type_id' => ['nullable', 'exists:work_types,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:15360'],
        ];
    }
}