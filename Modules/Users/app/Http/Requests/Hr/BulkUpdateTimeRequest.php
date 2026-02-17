<?php

namespace Modules\Users\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => 'nullable|integer|exists:departments,id',
            'sub_department_id' => 'nullable|integer|exists:sub_departments,id',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'working_hours_day' => 'nullable|numeric|min:0|max:24',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->department_id && !$this->sub_department_id) {
                $validator->errors()->add('filter', 'Either department_id or sub_department_id must be provided.');
            }
            
            if (!$this->start_time && !$this->end_time && !$this->working_hours_day) {
                $validator->errors()->add('fields', 'At least one of start_time, end_time or working_hours_day must be provided.');
            }
        });
    }
}
