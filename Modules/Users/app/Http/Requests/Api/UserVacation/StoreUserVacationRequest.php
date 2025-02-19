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
            'sick_left' => ['required', 'integer', 'max:5'],
            'paid_left' => ['required', 'integer', 'max:15'],
            'deduction_left' => ['required', 'integer', 'max:1'],
            'user_id' => ['required', 'exists:users,id'],

        ];
    }
}
