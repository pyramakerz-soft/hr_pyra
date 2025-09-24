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
            'grace_minutes' => 'nullable|integer|min:0|max:1440',
            'rules' => 'nullable|array',
            'rules.*.label' => 'required_with:rules|string|max:255',
            'rules.*.category' => 'required_with:rules|string|max:100',
            'rules.*.scope' => 'required_with:rules|string|max:100',
            'rules.*.order' => 'nullable|integer|min:0|max:1000',
            'rules.*.when' => 'required_with:rules|array',
            'rules.*.when.*' => 'nullable',
            'rules.*.penalty' => 'required_with:rules|array',
            'rules.*.penalty.type' => 'required_with:rules|string|max:100',
            'rules.*.penalty.value' => 'nullable|numeric',
            'rules.*.penalty.unit' => 'nullable|string|max:50',
            'rules.*.color' => 'nullable|string|max:20',
            'rules.*.notes' => 'nullable|string',
            'rules.*.stop_processing' => 'sometimes|boolean',
            'rules.*.meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'rules.*.label.required_with' => 'Each rule must include a label.',
            'rules.*.category.required_with' => 'Each rule must define a category.',
            'rules.*.scope.required_with' => 'Each rule must specify a scope.',
            'rules.*.penalty.type.required_with' => 'Each rule must define a penalty type.',
        ];
    }
}
