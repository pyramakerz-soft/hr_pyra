<?php

namespace Modules\Users\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $user = $this->route('user'); // Assuming you're passing the user ID as a route parameter
        // dd($user->id);
        return [
            'name' => ['nullable', 'string', 'min:3'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable',
                'regex:/^01[0125][0-9]{8}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'contact_phone' => [
                'nullable',
                'regex:/^01[0125][0-9]{8}$/',
                Rule::unique('users', 'contact_phone')->ignore($user->id),
            ],
            'national_id' => [
                'nullable',
                'string',
                'regex:/^[0-9]{14}$/',
                Rule::unique('users', 'national_id')->ignore($user->id),
            ],

            'code' => ['nullable', 'string'],
            'gender' => ['nullable', 'in:m,M,F,f'],
            'department_id' => ['nullable', 'exists:departments,id'],
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
            // 'is_float' => ['nullable'],
        ];
    }
    public function withValidator($validator)
    {
        $validator->sometimes('image', ['image', 'mimes:jpeg,png,jpg', 'max:15360'], function ($input) {
            // Apply the image rules only if an image file is uploaded
            return $this->hasFile('image');
        });
    }
}