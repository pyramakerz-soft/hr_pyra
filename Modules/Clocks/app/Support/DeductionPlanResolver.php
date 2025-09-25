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

        $departmentPlan = optional($user->department)->deductionPlan;
        $subDepartmentPlan = optional($user->subDepartment)->deductionPlan;
        $userPlan = $user->deductionPlan;

        $includeDepartment = $departmentPlan !== null;
        $includeSubDepartment = $subDepartmentPlan !== null;

        if ($subDepartmentPlan && $subDepartmentPlan->overwrite) {
            $includeDepartment = false;
        }

        $userOverwrite = false;
        $userOverwriteDepartment = false;
        $userOverwriteSubDepartment = false;

        if ($userPlan) {
            $userOverwrite = (bool) $userPlan->overwrite;
            $userOverwriteDepartment = (bool) ($userPlan->overwrite_dep ?? false);
            $userOverwriteSubDepartment = (bool) ($userPlan->overwrite_subdep ?? false);

            if ($userOverwrite) {
                $includeDepartment = false;
                $includeSubDepartment = false;
            } else {
                if ($userOverwriteDepartment) {
                    $includeDepartment = false;
                }

                if ($userOverwriteSubDepartment) {
                    $includeSubDepartment = false;
                }
            }
        }

        if ($includeDepartment) {
            $resolved = $this->applyPlan($resolved, $departmentPlan, 'Department', [
                'overwrite' => false,
            ]);
        }

        if ($includeSubDepartment) {
            $resolved = $this->applyPlan($resolved, $subDepartmentPlan, 'SubDepartment', [
                'overwrite' => (bool) $subDepartmentPlan->overwrite,
            ]);
        }

        if ($userPlan) {
            $resolved = $this->applyPlan($resolved, $userPlan, 'User', [
                'overwrite' => $userOverwrite,
                'overwrite_dep' => $userOverwriteDepartment || $userOverwrite,
                'overwrite_subdep' => $userOverwriteSubDepartment || $userOverwrite,
            ]);
        }

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

    protected function applyPlan(array $resolved, ?DeductionPlan $plan, string $type, array $metadata = []): array
    {
        if (! $plan) {
            return $resolved;
        }

        $planId = $plan->planable_id;

        $overwrite = (bool) ($metadata['overwrite'] ?? $plan->overwrite);
        $overwriteDepartment = (bool) ($metadata['overwrite_dep'] ?? $plan->overwrite_dep ?? false);
        $overwriteSubDepartment = (bool) ($metadata['overwrite_subdep'] ?? $plan->overwrite_subdep ?? false);

        $rules = collect($plan->rules ?? [])
            ->map(function (array $rule, int $index) use ($type, $planId, $overwrite, $overwriteDepartment, $overwriteSubDepartment) {
                if (! array_key_exists('order', $rule)) {
                    $rule['order'] = $index;
                }

                $rule['source'] = [
                    'type' => $type,
                    'id' => $planId,
                    'overwrite' => $overwrite,
                    'overwrite_dep' => $overwriteDepartment,
                    'overwrite_subdep' => $overwriteSubDepartment,
                ];

                if (! isset($rule['color']) || $rule['color'] === '') {
                    $rule['color'] = null;
                }

                return $rule;
            })
            ->all();

        if ($overwrite) {
            $resolved['rules'] = $rules;
            $resolved['sources'] = [[
                'type' => $type,
                'id' => $planId,
                'overwrite' => true,
                'overwrite_dep' => $overwriteDepartment,
                'overwrite_subdep' => $overwriteSubDepartment,
            ]];
        } else {
            $resolved['rules'] = array_merge($resolved['rules'], $rules);
            $resolved['sources'][] = [
                'type' => $type,
                'id' => $planId,
                'overwrite' => false,
                'overwrite_dep' => $overwriteDepartment,
                'overwrite_subdep' => $overwriteSubDepartment,
            ];
        }

        if ($plan->grace_minutes !== null) {
            $resolved['grace_minutes'] = (int) $plan->grace_minutes;
        }

        return $resolved;
    }
}
