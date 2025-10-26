<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Users\Enums\NotificationType;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $scopeRequiresId = Rule::requiredIf(function () {
            return in_array($this->input('scope_type'), ['department', 'sub_department', 'user'], true);
        });

        return [
            'type' => ['required', Rule::in(NotificationType::values())],
            'title' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string'],
            'scope_type' => ['required', Rule::in(['all', 'department', 'sub_department', 'user', 'custom'])],
            'scope_id' => [$scopeRequiresId, 'nullable', 'integer'],
            'user_ids' => [
                'array',
                Rule::requiredIf(fn () => $this->input('scope_type') === 'custom'),
            ],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'filters' => ['array'],
            'filters.roles' => ['array'],
            'filters.roles.*' => ['string'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}

