<?php

namespace Modules\Users\Http\Requests\Api\UserHolidays;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserHolidayRequest extends FormRequest
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
            'name' => ['nullable', 'string'],
            'date_of_holiday' => ['nullable', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
    }
}
