<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Modules\Clocks\Http\Requests\StoreDeductionRuleTemplateRequest;
use Modules\Clocks\Models\DeductionRuleTemplate;

class DeductionRuleTemplateController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $templates = DeductionRuleTemplate::orderBy('name')->get();

        return $this->returnData('data', [
            'templates' => $templates,
        ], 'Deduction rule templates retrieved successfully.');
    }

    public function store(StoreDeductionRuleTemplateRequest $request)
    {
        $data = $this->preparePayload($request->validated());

        $template = DeductionRuleTemplate::create($data);

        return $this->returnData('data', [
            'template' => $template->fresh(),
        ], 'Deduction rule template created successfully.');
    }

    public function show(DeductionRuleTemplate $template)
    {
        return $this->returnData('data', [
            'template' => $template,
        ], 'Deduction rule template retrieved successfully.');
    }

    public function update(StoreDeductionRuleTemplateRequest $request, DeductionRuleTemplate $template)
    {
        $data = $this->preparePayload($request->validated());

        $template->fill($data);
        $template->save();

        return $this->returnData('data', [
            'template' => $template->fresh(),
        ], 'Deduction rule template updated successfully.');
    }

    public function destroy(DeductionRuleTemplate $template)
    {
        $template->delete();

        return $this->returnSuccessMessage('Deduction rule template deleted successfully.');
    }

    protected function preparePayload(array $data): array
    {
        $rule = $data['rule'] ?? [];
        $primaryRule = $this->extractPrimaryRule($rule);

        if (! isset($data['category']) && isset($primaryRule['category'])) {
            $data['category'] = $primaryRule['category'];
        }

        if (! isset($data['scope']) && isset($primaryRule['scope'])) {
            $data['scope'] = $primaryRule['scope'];
        }

        return $data;
    }

    protected function extractPrimaryRule(array $rule): array
    {
        $isAssociative = $this->isAssociative($rule);

        if ($isAssociative) {
            return $rule;
        }

        return $rule[0] ?? [];
    }

    protected function isAssociative(array $array): bool
    {
        if ([] === $array) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
