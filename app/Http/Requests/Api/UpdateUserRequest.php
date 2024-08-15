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
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'contact_phone' => ['required', 'regex:/^01[0125][0-9]{8}$/'],
            'code' => ['required', 'numeric', 'min:6'],
            'national_id' => ['required', 'string', 'regex:/^[0-9]{14}$/'],
            'gender' => ['required', 'in:m,M,F,f'],
            'department_id' => ['required', 'exists:departments,id'],
            'image' => ['required'],
            'roles' => ['required'],

        ];
    }
}
