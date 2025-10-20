<?php

namespace Modules\Clocks\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Clocks\Models\DeductionRuleTemplate;

class DeductionPlanPayloadManager
{
    public function buildPayload(array $data, bool $supportsOverwrite = true): array
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
                if (! is_array($expandedRule)) {
                    continue;
                }

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
            $payload['overwrite'] = (bool) Arr::get($data, 'overwrite', false);
            $payload['overwrite_dep'] = (bool) Arr::get($data, 'overwrite_dep', false);
            $payload['overwrite_subdep'] = (bool) Arr::get($data, 'overwrite_subdep', false);
        } else {
            $payload['overwrite'] = false;
            $payload['overwrite_dep'] = false;
            $payload['overwrite_subdep'] = false;
        }

        return $payload;
    }

    /**
     * @return array{0:Collection,1:Collection}
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

    protected function expandRuleEntry(array $rulePayload, Collection $templatesByKey, Collection $templatesById): array
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
        $definitions = $this->filterDefinitionsForRule($definitions, $rulePayload);

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

    protected function filterDefinitionsForRule(array $definitions, array $rulePayload): array
    {
        if (count($definitions) <= 1) {
            return $definitions;
        }

        $ruleMeta = Arr::get($rulePayload, 'meta');
        if (is_array($ruleMeta) && ! empty($ruleMeta)) {
            $candidates = array_filter($definitions, static function ($definition) use ($ruleMeta) {
                $definitionMeta = Arr::get($definition, 'meta');

                if (! is_array($definitionMeta) || empty($definitionMeta)) {
                    return false;
                }

                $matchingKeys = [
                    'sequence_step',
                    'sequence',
                    'template_step',
                    'step',
                    'slug',
                    'identifier',
                ];

                foreach ($matchingKeys as $key) {
                    if (array_key_exists($key, $ruleMeta) && array_key_exists($key, $definitionMeta)) {
                        if ((string) $ruleMeta[$key] === (string) $definitionMeta[$key]) {
                            return true;
                        }
                    }
                }

                return false;
            });

            if (! empty($candidates)) {
                return array_values($candidates);
            }
        }

        $label = Arr::get($rulePayload, 'label');
        if ($label) {
            $candidates = array_filter($definitions, static function ($definition) use ($label) {
                return isset($definition['label']) && $definition['label'] === $label;
            });

            if (! empty($candidates)) {
                return array_values($candidates);
            }
        }

        return $definitions;
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

    protected function findTemplate($templateId, $templateKey, Collection $templatesByKey, Collection $templatesById): ?DeductionRuleTemplate
    {
        if ($templateKey !== null) {
            /** @var DeductionRuleTemplate|null $candidate */
            $candidate = $templatesByKey->get($templateKey);
            if ($candidate) {
                return $candidate;
            }
        }

        if ($templateId !== null) {
            /** @var DeductionRuleTemplate|null $candidate */
            $candidate = $templatesById->get((int) $templateId);
            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }
}
