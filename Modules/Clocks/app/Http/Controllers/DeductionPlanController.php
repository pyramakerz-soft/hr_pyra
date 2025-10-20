<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Modules\Clocks\Http\Requests\StoreDeductionPlanRequest;
use Modules\Clocks\Support\DeductionPlanPayloadManager;
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
        /** @var DeductionPlanPayloadManager $payloadManager */
        $payloadManager = app(DeductionPlanPayloadManager::class);
        $payload = $payloadManager->buildPayload($request->validated(), $supportsOverwrite);
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
}
