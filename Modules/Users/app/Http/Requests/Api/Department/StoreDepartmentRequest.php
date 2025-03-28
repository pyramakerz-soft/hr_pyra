<?php

namespace Modules\Users\Http\Requests\Api\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'unique:departments,name'],
            'is_location_time' => ['required', 'boolean'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ];
    }

}
