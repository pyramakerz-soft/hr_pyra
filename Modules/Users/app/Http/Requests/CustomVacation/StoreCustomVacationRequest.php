<?php

namespace Modules\Users\Http\Requests\CustomVacation;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_full_day' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'integer|exists:departments,id',
            'sub_department_ids' => 'nullable|array',
            'sub_department_ids.*' => 'integer|exists:sub_departments,id',
        ];
    }
}

