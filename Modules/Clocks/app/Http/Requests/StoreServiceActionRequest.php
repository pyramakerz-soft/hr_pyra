<?php

namespace Modules\Clocks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Clocks\Support\ServiceActionRegistry;

class StoreServiceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $scopedRequirement = Rule::requiredIf(function () {
            return in_array($this->input('scope_type'), ['department', 'sub_department', 'user'], true);
        });

        return [
            'action_type' => ['required', Rule::in(ServiceActionRegistry::keys())],
            'scope_type' => ['required', Rule::in(['all', 'department', 'sub_department', 'user', 'custom'])],
            'scope_id' => [$scopedRequirement, 'nullable', 'integer'],
            'user_ids' => [
                'array',
                Rule::requiredIf(function () {
                    return $this->input('scope_type') === 'custom';
                }),
            ],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'payload' => ['array'],
            'payload.date' => ['nullable', 'date'],
            'payload.from_date' => ['nullable', 'date'],
            'payload.to_date' => ['nullable', 'date', 'after_or_equal:payload.from_date'],
            'payload.clock_out_time' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'payload.default_duration_minutes' => ['nullable', 'integer', 'min:60', 'max:1440'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $scopeType = $this->input('scope_type');

        if (in_array($scopeType, ['department', 'sub_department', 'user'], true)) {
            $this->merge([
                'scope_id' => $this->input('scope_id') ?: null,
            ]);
        } else {
            $this->merge([
                'scope_id' => null,
            ]);
        }
    }
}
