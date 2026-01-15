<?php

namespace Modules\Users\Http\Requests\Api\OverTime;

use Illuminate\Foundation\Http\FormRequest;

class StartUserOverTimeRequest extends FormRequest
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
    public function rules()
    {
        return [
            'minutes' => 'required|numeric',   // Validate time format

        ];
    }
}
