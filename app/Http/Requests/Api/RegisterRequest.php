<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

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
            'gender' => ['required', 'in:m,M,F,f'],
            'department_id' => ['required', 'exists:departments,id'],
            'image' => ['nullable'],

        ];
    }

}
