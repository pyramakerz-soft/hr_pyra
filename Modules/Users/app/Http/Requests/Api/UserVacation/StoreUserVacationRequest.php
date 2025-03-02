<?php

namespace Modules\Users\Http\Requests\Api\UserVacation;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserVacationRequest extends FormRequest
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
            'from_date' => 'required|date_format:Y-m-d',
            'to_date' => 'required|date_format:Y-m-d',
            'status' => 'nullable|in:pending,approved,rejected',

        ];
    }
}
