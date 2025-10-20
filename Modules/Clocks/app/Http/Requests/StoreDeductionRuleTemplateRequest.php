<?php

namespace Modules\Clocks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Clocks\Models\DeductionRuleTemplate;

class StoreDeductionRuleTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var DeductionRuleTemplate|null $template */
        $template = $this->route('template');

        return [
            'key' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('deduction_rule_templates', 'key')->ignore($template?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'scope' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'rule' => ['required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
