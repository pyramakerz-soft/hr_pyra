<?php

namespace Modules\Users\Http\Requests\Api\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        $department = $this->route('department');
        return [
            'name' => ['nullable', 'string', Rule::unique('departments', 'name')->ignore($department->id)],
            'is_location_time' => ['nullable', 'boolean'],

            'manager_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
