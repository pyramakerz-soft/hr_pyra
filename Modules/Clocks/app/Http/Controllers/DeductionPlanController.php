<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Modules\Clocks\Http\Requests\StoreDeductionPlanRequest;
use Modules\Clocks\Models\DeductionRuleTemplate;
use Modules\Clocks\Support\DeductionPlanResolver;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\User;

class DeductionPlanController extends Controller
{
    use ResponseTrait;

    public function showDepartment(Department $department)
    {
        $department->loadMissing('deductionPlan');

        return $this->returnPlanData($department, 'Department deduction plan data retrieved.');
    }

    public function upsertDepartment(StoreDeductionPlanRequest $request, Department $department)
    {
        $plan = $this->persistPlan($request, $department, supportsOverwrite: false);

        $department->load('deductionPlan');

        return $this->returnData('data', [
            'plan' => $plan,
        ], 'Department deduction plan saved successfully.');
    }

    public function showSubDepartment(SubDepartment $subDepartment)
    {
        $subDepartment->loadMissing('deductionPlan');

        return $this->returnPlanData($subDepartment, 'Sub-department deduction plan data retrieved.');
    }

    public function upsertSubDepartment(StoreDeductionPlanRequest $request, SubDepartment $subDepartment)
    {
        $plan = $this->persistPlan($request, $subDepartment);

        $subDepartment->load('deductionPlan');

        return $this->returnData('data', [
            'plan' => $plan,
        ], 'Sub-department deduction plan saved successfully.');
    }

    public function showUser(User $user)
    {
        $user->loadMissing(['deductionPlan', 'department.deductionPlan', 'subDepartment.deductionPlan']);

        $resolver = new DeductionPlanResolver();
        $resolved = $resolver->resolveForUser($user);

        return $this->returnPlanData($user, 'Employee deduction plan data retrieved.', $resolved);
    }

    public function upsertUser(StoreDeductionPlanRequest $request, User $user)
    {
        $plan = $this->persistPlan($request, $user);

        $user->load(['deductionPlan', 'department.deductionPlan', 'subDepartment.deductionPlan']);

        $resolver = new DeductionPlanResolver();
        $resolved = $resolver->resolveForUser($user);

        return $this->returnData('data', [
            'plan' => $plan,
            'effective_plan' => $resolved,
        ], 'Employee deduction plan saved successfully.');
    }

    protected function returnPlanData($planable, string $message, ?array $resolved = null)
    {
        $plan = $planable->deductionPlan;

        $payload = [
            'plan' => $plan,
            'planable' => [
                'id' => $planable->getKey(),
                'type' => class_basename($planable),
            ],
        ];

        if ($resolved !== null) {
            $payload['effective_plan'] = $resolved;
        }

        return $this->returnData('data', $payload, $message);
    }

    protected function persistPlan(StoreDeductionPlanRequest $request, $planable, bool $supportsOverwrite = true)
    {
        $payload = $this->normalizePayload($request->validated(), $supportsOverwrite);
        $relation = $planable->deductionPlan();

        $plan = $relation->first();

        if ($plan) {
            $plan->fill($payload);
            $plan->save();
        } else {
            $plan = $relation->create($payload);
        }

        return $plan->fresh();
    }

    protected function normalizePayload(array $data, bool $supportsOverwrite): array
    {
        $rawRules = Arr::get($data, 'rules', []);
        [$templatesByKey, $templatesById] = $this->loadTemplateLookups($rawRules);

        $normalizedRules = [];
        foreach ($rawRules as $index => $rulePayload) {
            if (! is_array($rulePayload)) {
                continue;
            }

            $baseOrder = isset($rulePayload['order']) ? (int) $rulePayload['order'] : $index;
            $expanded = $this->expandRuleEntry($rulePayload, $templatesByKey, $templatesById);

            foreach ($expanded as $subIndex => $expandedRule) {
                $normalizedRules[] = [
                    'order_marker' => ($baseOrder * 1000) + $subIndex,
                    'rule' => $this->sanitizeRule($expandedRule),
                ];
            }
        }

        $rules = collect($normalizedRules)
            ->sortBy('order_marker')
            ->values()
            ->map(function (array $item, int $position) {
                $rule = $item['rule'];
                $rule['order'] = $position;

                return $rule;
            })
            ->all();

        $payload = [
            'rules' => $rules,
            'grace_minutes' => Arr::get($data, 'grace_minutes'),
        ];

        if ($supportsOverwrite) {
            $payload['overwrite'] = (bool) (Arr::get($data, 'overwrite', false));
            $payload['overwrite_dep'] = (bool) (Arr::get($data, 'overwrite_dep', false));
            $payload['overwrite_subdep'] = (bool) (Arr::get($data, 'overwrite_subdep', false));
        } else {
            $payload['overwrite'] = false;
            $payload['overwrite_dep'] = false;
            $payload['overwrite_subdep'] = false;
        }

        return $payload;
    }

    /**
     * @return array{0:\Illuminate\Support\Collection,1:\Illuminate\Support\Collection}
     */
    protected function loadTemplateLookups(array $rules): array
    {
        $keys = collect($rules)
            ->pluck('template_key')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ids = collect($rules)
            ->pluck('template_id')
            ->filter()
            ->map(static function ($value) {
                return (int) $value;
            })
            ->unique()
            ->values()
            ->all();

        if (empty($keys) && empty($ids)) {
            return [collect(), collect()];
        }

        $query = DeductionRuleTemplate::query();
        $hasWhere = false;

        if (! empty($keys)) {
            $query->whereIn('key', $keys);
            $hasWhere = true;
        }

        if (! empty($ids)) {
            $method = $hasWhere ? 'orWhereIn' : 'whereIn';
            $query->{$method}('id', $ids);
        }

        $templates = $query->get();

        return [$templates->keyBy('key'), $templates->keyBy('id')];
    }

    protected function expandRuleEntry(array $rulePayload, $templatesByKey, $templatesById): array
    {
        $templateKey = Arr::get($rulePayload, 'template_key');
        $templateId = Arr::get($rulePayload, 'template_id');

        if ($templateKey === null && $templateId === null) {
            return [Arr::except($rulePayload, ['order', 'overrides', 'template_id', 'template_key'])];
        }

        $template = $this->findTemplate($templateId, $templateKey, $templatesByKey, $templatesById);

        if (! $template) {
            throw ValidationException::withMessages([
                'rules' => ['Unable to locate the requested deduction rule template.'],
            ]);
        }

        $definitions = $this->normalizeTemplateDefinition($template->rule ?? []);

        if (empty($definitions)) {
            throw ValidationException::withMessages([
                'rules' => ["Template {$template->key} does not define any usable rules."],
            ]);
        }

        $overrides = Arr::get($rulePayload, 'overrides', []);
        $directOverrides = Arr::only($rulePayload, [
            'label',
            'category',
            'scope',
            'when',
            'penalty',
            'notes',
            'color',
            'stop_processing',
            'meta',
        ]);

        $expanded = [];
        foreach ($definitions as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $rule = $this->applyRuleOverrides($definition, is_array($overrides) ? $overrides : [], true);
            $rule = $this->applyRuleOverrides($rule, $directOverrides, false);

            $rule['template_id'] = (int) $template->id;
            $rule['template_key'] = $template->key;
            $rule['template_name'] = $template->name;
            $rule['template_category'] = $template->category;
            $rule['template_scope'] = $template->scope;

            $expanded[] = $rule;
        }

        return $expanded;
    }

    protected function normalizeTemplateDefinition($definition): array
    {
        if (! is_array($definition)) {
            return [];
        }

        if ($this->isAssociative($definition)) {
            return [$definition];
        }

        return array_values(array_filter($definition, 'is_array'));
    }

    protected function applyRuleOverrides(array $rule, array $overrides, bool $mergeNested): array
    {
        foreach ($overrides as $key => $value) {
            if (in_array($key, ['when', 'penalty', 'meta'], true) && $mergeNested) {
                $existing = isset($rule[$key]) && is_array($rule[$key]) ? $rule[$key] : [];
                $rule[$key] = array_replace_recursive($existing, is_array($value) ? $value : []);
            } else {
                $rule[$key] = $value;
            }
        }

        return $rule;
    }

    protected function sanitizeRule(array $rule): array
    {
        unset($rule['order'], $rule['overrides']);

        $rule['stop_processing'] = (bool) ($rule['stop_processing'] ?? false);

        if (isset($rule['penalty']) && is_array($rule['penalty'])) {
            $penalty = $rule['penalty'];

            if (array_key_exists('value', $penalty) && $penalty['value'] !== null && $penalty['value'] !== '') {
                $penalty['value'] = (float) $penalty['value'];
            }

            $rule['penalty'] = $penalty;
        }

        if (! isset($rule['color']) || $rule['color'] === '') {
            $rule['color'] = null;
        }

        if (isset($rule['meta']) && ! is_array($rule['meta'])) {
            $rule['meta'] = [];
        }

        return $rule;
    }

    protected function isAssociative(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function findTemplate($templateId, $templateKey, $templatesByKey, $templatesById): ?DeductionRuleTemplate
    {
        if ($templateKey !== null && $templatesByKey instanceof \Illuminate\Support\Collection) {
            $candidate = $templatesByKey->get($templateKey);
            if ($candidate) {
                return $candidate;
            }
        }

        if ($templateId !== null && $templatesById instanceof \Illuminate\Support\Collection) {
            $candidate = $templatesById->get((int) $templateId);
            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }
}
