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
            'from_date' => 'required|date',
            'to_date' => 'required|date',
            'vacation_type_id' => 'nullable|exists:vacation_types,id',
            'is_half_day' => 'nullable|boolean',
            'attachments' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value)) {
                        return;
                    }
                    if ($value instanceof \Illuminate\Http\UploadedFile) {
                        if ($value->getSize() > 10240 * 1024) {
                            $fail('The ' . $attribute . ' must not be greater than 10MB.');
                        }
                    } else {
                        $fail('The ' . $attribute . ' must be a file or array of files.');
                    }
                }
            ],
            'attachments.*' => 'file|max:10240', // Max 10MB per file
        ];
    }
}
