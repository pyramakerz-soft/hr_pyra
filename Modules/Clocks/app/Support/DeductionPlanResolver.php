<?php

namespace Modules\Clocks\Support;

use Illuminate\Support\Arr;
use Modules\Clocks\Models\DeductionPlan;
use Modules\Users\Models\User;

class DeductionPlanResolver
{
    public function resolveForUser(User $user): array
    {
        $resolved = [
            'rules' => [],
            'grace_minutes' => null,
            'sources' => [],
        ];

        $resolved = $this->applyPlan($resolved, optional($user->department)->deductionPlan, 'Department');
        $resolved = $this->applyPlan($resolved, optional($user->subDepartment)->deductionPlan, 'SubDepartment');
        $resolved = $this->applyPlan($resolved, $user->deductionPlan, 'User');

        if ($resolved['grace_minutes'] === null) {
            $resolved['grace_minutes'] = 15;
        }

        $resolved['rules'] = collect($resolved['rules'])
            ->sortBy(function (array $rule, int $index) {
                return Arr::get($rule, 'order', $index);
            })
            ->values()
            ->all();

        return $resolved;
    }

    protected function applyPlan(array $resolved, ?DeductionPlan $plan, string $type): array
    {
        if (! $plan) {
            return $resolved;
        }

        $planId = $plan->planable_id;

        $rules = collect($plan->rules ?? [])
            ->map(function (array $rule, int $index) use ($plan, $type, $planId) {
                if (! array_key_exists('order', $rule)) {
                    $rule['order'] = $index;
                }

                $rule['source'] = [
                    'type' => $type,
                    'id' => $planId,
                    'overwrite' => (bool) $plan->overwrite,
                ];

                if (! isset($rule['color']) || $rule['color'] === '') {
                    $rule['color'] = null;
                }

                return $rule;
            })
            ->all();

        if ($plan->overwrite) {
            $resolved['rules'] = $rules;
            $resolved['sources'] = [[
                'type' => $type,
                'id' => $planId,
                'overwrite' => true,
            ]];
        } else {
            $resolved['rules'] = array_merge($resolved['rules'], $rules);
            $resolved['sources'][] = [
                'type' => $type,
                'id' => $planId,
                'overwrite' => false,
            ];
        }

        if ($plan->grace_minutes !== null) {
            $resolved['grace_minutes'] = (int) $plan->grace_minutes;
        }

        return $resolved;
    }
}
