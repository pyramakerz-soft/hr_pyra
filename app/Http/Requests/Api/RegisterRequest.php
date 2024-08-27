<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6'],
            'phone' => ['required', 'unique:users,phone', 'regex:/^01[0125][0-9]{8}$/'],
            'contact_phone' => ['required', 'unique:users,contact_phone', 'regex:/^01[0125][0-9]{8}$/'],
            'national_id' => ['required', 'string', 'unique:users,national_id', 'regex:/^[0-9]{14}$/'],
            'code' => ['nullable', 'string'],
            'gender' => ['required', 'in:m,M,F,f'],
            'department_id' => ['required', 'exists:departments,id'],
            'image' => ['nullable'],
            'salary' => ['required', 'numeric'],
            'working_hours_day' => ['required', 'numeric', 'min:4'],
            'overtime_hours' => ['required', 'numeric'],
            'emp_type' => ['required', 'string'],
            'hiring_date' => ['required', 'date'],
            'user_id' => ['exists:users,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'location_id' => ['required', 'exists:locations,id'],
            'work_type_id' => ['required', 'exists:work_types,id'],
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:15360'],
            'serial_number' => ['nullable', 'string'],
        ];
    }

}