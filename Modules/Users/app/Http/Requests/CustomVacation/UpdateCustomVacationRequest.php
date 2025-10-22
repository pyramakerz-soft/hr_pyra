<?php

namespace Modules\Users\Http\Requests\CustomVacation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'is_full_day' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
            'sub_department_ids' => 'nullable|array',
            'sub_department_ids.*' => 'integer|exists:sub_departments,id',
        ];
    }
}
