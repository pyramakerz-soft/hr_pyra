<?php

namespace Modules\Users\Http\Requests\Api\UserVacation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserVacationRequest extends FormRequest
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
            'sick_left' => ['nullable', 'integer'],
            'paid_left' => ['nullable', 'integer'],
            'deduction_left' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'exists:users,id'],

        ];
    }
}
