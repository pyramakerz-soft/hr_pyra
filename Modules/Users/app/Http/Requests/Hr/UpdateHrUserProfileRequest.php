<?php

namespace Modules\Users\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHrUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'detail' => 'sometimes|array',
            'detail.salary' => 'nullable|numeric|min:0',
            'detail.hourly_rate' => 'nullable|numeric|min:0',
            'detail.overtime_hourly_rate' => 'nullable|numeric|min:0',
            'detail.working_hours_day' => 'nullable|numeric|min:0',
            'detail.overtime_hours' => 'nullable|numeric|min:0',
            'detail.start_time' => 'nullable|date_format:H:i',
            'detail.end_time' => 'nullable|date_format:H:i',
            'detail.emp_type' => 'nullable|string|max:255',
            'detail.hiring_date' => 'nullable|date',

            'vacation_balances' => 'sometimes|array',
            'vacation_balances.*.id' => 'nullable|integer|exists:user_vacation_balances,id',
            'vacation_balances.*.vacation_type_id' => 'required_without:vacation_balances.*.id|integer|exists:vacation_types,id',
            'vacation_balances.*.year' => 'nullable|integer|min:1900|max:2100',
            'vacation_balances.*.allocated_days' => 'required_with:vacation_balances.*.vacation_type_id|numeric|min:0',
            'vacation_balances.*.used_days' => 'nullable|numeric|min:0',

            'vacation_balance_ids_to_delete' => 'sometimes|array',
            'vacation_balance_ids_to_delete.*' => 'integer|exists:user_vacation_balances,id',
        ];
    }
}

