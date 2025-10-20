<?php

namespace Modules\Clocks\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeductionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overwrite' => 'sometimes|boolean',
            'overwrite_dep' => 'sometimes|boolean',
            'overwrite_subdep' => 'sometimes|boolean',
            'grace_minutes' => 'nullable|integer|min:0|max:1440',
            'rules' => 'nullable|array',
            'rules.*.template_id' => 'nullable|integer|exists:deduction_rule_templates,id',
            'rules.*.template_key' => 'nullable|string|exists:deduction_rule_templates,key',
            'rules.*.label' => 'required_without_all:rules.*.template_id,rules.*.template_key|string|max:255',
            'rules.*.category' => 'required_without_all:rules.*.template_id,rules.*.template_key|string|max:100',
            'rules.*.scope' => 'required_without_all:rules.*.template_id,rules.*.template_key|string|max:100',
            'rules.*.order' => 'nullable|integer|min:0|max:1000',
            'rules.*.when' => 'required_without_all:rules.*.template_id,rules.*.template_key|array',
            'rules.*.when.*' => 'nullable',
            'rules.*.penalty' => 'required_without_all:rules.*.template_id,rules.*.template_key|array',
            'rules.*.penalty.type' => 'required_without_all:rules.*.template_id,rules.*.template_key|string|max:100',
            'rules.*.penalty.value' => 'nullable|numeric',
            'rules.*.penalty.unit' => 'nullable|string|max:50',
            'rules.*.color' => 'nullable|string|max:20',
            'rules.*.notes' => 'nullable|string',
            'rules.*.stop_processing' => 'sometimes|boolean',
            'rules.*.meta' => 'nullable|array',
            'rules.*.overrides' => 'nullable|array',
            'rules.*.overrides.penalty' => 'nullable|array',
            'rules.*.overrides.penalty.value' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'rules.*.label.required_without_all' => 'Each custom rule must include a label.',
            'rules.*.category.required_without_all' => 'Each custom rule must define a category.',
            'rules.*.scope.required_without_all' => 'Each custom rule must specify a scope.',
            'rules.*.penalty.type.required_without_all' => 'Each custom rule must define a penalty type.',
        ];
    }
}
