<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Clocks\Http\Requests\StoreDeductionPlanRequest;
use Modules\Clocks\Models\DeductionRuleTemplate;
use Modules\Clocks\Support\DeductionPlanPayloadManager;
use Modules\Clocks\Support\DeductionPlanResolver;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\User;

class RulesPageController extends Controller
{
    use ResponseTrait;

    public function index(): View
    {
        $templates = DeductionRuleTemplate::query()
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->with(['subDepartments:id,department_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id', 'sub_department_id', 'code']);

        return view('clocks::rules.index', [
            'templates' => $templates,
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    public function showPlan(string $scope, int $id): JsonResponse
    {
        [$planable, $context] = $this->resolvePlanableOrFail($scope, $id);

        $planable->loadMissing('deductionPlan');

        $payload = [
            'plan' => $planable->deductionPlan,
            'planable' => [
                'id' => $planable->getKey(),
                'type' => class_basename($planable),
                'name' => $planable->name ?? ($planable->code ?? 'Record #' . $planable->getKey()),
            ],
            'context' => $context,
        ];

        if ($context['scope'] === 'user') {
            $planable->loadMissing(['department.deductionPlan', 'subDepartment.deductionPlan']);
            $resolver = new DeductionPlanResolver();
            $payload['effective_plan'] = $resolver->resolveForUser($planable);
        }

        return $this->returnData('data', $payload, 'Plan data retrieved successfully.');
    }

    public function updatePlan(
        StoreDeductionPlanRequest $request,
        string $scope,
        int $id,
        DeductionPlanPayloadManager $payloadManager
    ): JsonResponse {
        [$planable, $context] = $this->resolvePlanableOrFail($scope, $id);

        $payload = $payloadManager->buildPayload($request->validated(), $context['supports_overwrite']);

        $relation = $planable->deductionPlan();
        $plan = $relation->first();

        if ($plan) {
            $plan->fill($payload);
            $plan->save();
        } else {
            $plan = $relation->create($payload);
        }

        $planable->load('deductionPlan');

        $response = [
            'plan' => $planable->deductionPlan,
            'planable' => [
                'id' => $planable->getKey(),
                'type' => class_basename($planable),
                'name' => $planable->name ?? ($planable->code ?? 'Record #' . $planable->getKey()),
            ],
            'context' => $context,
        ];

        if ($context['scope'] === 'user') {
            $planable->load(['department.deductionPlan', 'subDepartment.deductionPlan']);
            $resolver = new DeductionPlanResolver();
            $response['effective_plan'] = $resolver->resolveForUser($planable);
        }

        return $this->returnData('data', $response, 'Plan saved successfully.');
    }

    /**
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function resolvePlanableOrFail(string $scope, int $id): array
    {
        $normalizedScope = str_replace('_', '-', strtolower($scope));

        switch ($normalizedScope) {
            case 'department':
                $planable = Department::findOrFail($id);
                $context = [
                    'scope' => 'department',
                    'supports_overwrite' => false,
                    'supports_overwrite_dep' => false,
                    'supports_overwrite_subdep' => false,
                    'label' => 'Department',
                ];
                break;
            case 'sub-department':
                $planable = SubDepartment::findOrFail($id);
                $context = [
                    'scope' => 'sub-department',
                    'supports_overwrite' => true,
                    'supports_overwrite_dep' => false,
                    'supports_overwrite_subdep' => false,
                    'label' => 'Sub Department',
                ];
                break;
            case 'user':
                $planable = User::findOrFail($id);
                $context = [
                    'scope' => 'user',
                    'supports_overwrite' => true,
                    'supports_overwrite_dep' => true,
                    'supports_overwrite_subdep' => true,
                    'label' => 'Employee',
                ];
                break;
            default:
                abort(404, 'Unknown plan scope.');
        }

        return [$planable, $context];
    }
}
