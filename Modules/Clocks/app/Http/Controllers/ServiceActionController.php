<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Clocks\Http\Requests\StoreServiceActionRequest;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Models\ServiceAction;
use Modules\Clocks\Support\ServiceActionRegistry;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\User;

class ServiceActionController extends Controller
{
    use ResponseTrait;

    public function available()
    {
        return $this->returnData('actions', [
            'definitions' => ServiceActionRegistry::all(),
            'scopes' => [
                ['key' => 'all', 'label' => 'Entire Organization'],
                ['key' => 'department', 'label' => 'Department'],
                ['key' => 'sub_department', 'label' => 'Sub Department'],
                ['key' => 'user', 'label' => 'Single Employee'],
                ['key' => 'custom', 'label' => 'Selected Employees'],
            ],
        ]);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('limit', 25);
        $perPage = max(1, min(100, $perPage));

        $query = ServiceAction::with('performer:id,name')
            ->when($request->filled('action_type'), fn (Builder $builder) => $builder->where('action_type', $request->string('action_type')))
            ->when($request->filled('scope_type'), fn (Builder $builder) => $builder->where('scope_type', $request->string('scope_type')))
            ->orderByDesc('created_at');

        $actions = $query->limit($perPage)->get();

        return $this->returnData('service_actions', $actions);
    }

    public function store(StoreServiceActionRequest $request)
    {
        $validated = $request->validated();
        $payload = $validated['payload'] ?? [];
        $scopeType = $validated['scope_type'];
        $scopeId = $validated['scope_id'] ?? null;

        $userIds = $this->resolveUserIds($scopeType, $scopeId, $validated['user_ids'] ?? []);

        if ($userIds->isEmpty()) {
            return $this->returnError('No employees matched the selected scope.');
        }

        $serviceAction = new ServiceAction([
            'action_type' => $validated['action_type'],
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'payload' => $payload,
            'status' => 'pending',
            'performed_by' => Auth::id(),
        ]);
        $serviceAction->save();

        try {
            $result = DB::transaction(function () use ($serviceAction, $userIds, $payload) {
                return $this->performAction($serviceAction, $userIds, $payload);
            });

            $serviceAction->forceFill([
                'status' => 'completed',
                'result' => $result,
            ])->save();

            return $this->returnData(
                'service_action',
                $serviceAction->fresh('performer'),
                'Service action executed successfully.'
            );
        } catch (\Throwable $throwable) {
            report($throwable);

            $serviceAction->forceFill([
                'status' => 'failed',
                'result' => [
                    'message' => $throwable->getMessage(),
                ],
            ])->save();

            return $this->returnError('Unable to complete service action. Please review the details and try again.');
        }
    }

    protected function performAction(ServiceAction $serviceAction, Collection $userIds, array $payload): array
    {
        return match ($serviceAction->action_type) {
            ServiceActionRegistry::ACTION_FORCE_CLOCK_OUT => $this->forceClockOut($userIds, $payload),
            ServiceActionRegistry::ACTION_RESOLVE_CLOCK_ISSUES => $this->resolveClockIssues($userIds, $payload),
            ServiceActionRegistry::ACTION_RECOMPUTE_DURATIONS => $this->recomputeDurations($userIds, $payload),
            default => throw new \InvalidArgumentException('Unknown service action supplied.'),
        };
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function resolveUserIds(string $scopeType, ?int $scopeId, array $userIds = []): Collection
    {
        return (match ($scopeType) {
            'department' => $this->usersForDepartment($scopeId)->pluck('id'),
            'sub_department' => $this->usersForSubDepartment($scopeId)->pluck('id'),
            'user' => collect($scopeId ? [$scopeId] : []),
            'custom' => User::query()->whereIn('id', $userIds)->pluck('id'),
            'all' => User::query()->pluck('id'),
            default => collect(),
        })->unique()->values();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function usersForDepartment(?int $departmentId): Builder
    {
        if (! $departmentId) {
            return User::query()->whereRaw('1 = 0');
        }

        $department = Department::findOrFail($departmentId);
        $subDepartmentIds = SubDepartment::query()->where('department_id', $department->id)->pluck('id');

        return User::query()
            ->where(function (Builder $builder) use ($department, $subDepartmentIds) {
                $builder->where('department_id', $department->id);

                if ($subDepartmentIds->isNotEmpty()) {
                    $builder->orWhereIn('sub_department_id', $subDepartmentIds);
                }
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function usersForSubDepartment(?int $subDepartmentId): Builder
    {
        if (! $subDepartmentId) {
            return User::query()->whereRaw('1 = 0');
        }

        SubDepartment::findOrFail($subDepartmentId);

        return User::query()->where('sub_department_id', $subDepartmentId);
    }

    protected function forceClockOut(Collection $userIds, array $payload): array
    {
        $targetDate = isset($payload['date'])
            ? Carbon::parse($payload['date'])->startOfDay()
            : Carbon::now()->startOfDay();

        $clockOutTime = $payload['clock_out_time'] ?? null;
        $defaultMinutes = (int) ($payload['default_duration_minutes'] ?? 540);
        $defaultMinutes = max(60, min($defaultMinutes, 960));
        $now = Carbon::now();

        $startOfDay = $targetDate->copy();
        $endOfDay = $targetDate->copy()->endOfDay();

        $affectedClocks = 0;
        $affectedUsers = collect();

        ClockInOut::query()
            ->whereIn('user_id', $userIds)
            ->whereNull('clock_out')
            ->whereBetween('clock_in', [$startOfDay, $endOfDay])
            ->orderBy('id')
            ->chunkById(100, function ($clocks) use (
                &$affectedClocks,
                $clockOutTime,
                $defaultMinutes,
                $now,
                &$affectedUsers
            ) {
                foreach ($clocks as $clock) {
                    $clockInAt = Carbon::parse($clock->clock_in);
                    $clockOutAt = $clockOutTime
                        ? $clockInAt->copy()->setTimeFromTimeString($clockOutTime)
                        : $clockInAt->copy()->addMinutes($defaultMinutes);

                    if ($clockOutAt->lessThanOrEqualTo($clockInAt)) {
                        $clockOutAt = $clockInAt->copy()->addMinutes($defaultMinutes);
                    }

                    if ($clockOutAt->greaterThan($now)) {
                        $clockOutAt = $now->copy();
                    }

                    $duration = $clockInAt->diff($clockOutAt)->format('%H:%I:%S');

                    $clock->update([
                        'clock_out' => $clockOutAt,
                        'duration' => $duration,
                        'is_issue' => false,
                    ]);

                    $affectedClocks++;
                    $affectedUsers->push($clock->user_id);
                }
            });

        return [
            'affected_clocks' => $affectedClocks,
            'unique_users' => $affectedUsers->unique()->values(),
            'applied_date' => $startOfDay->toDateString(),
        ];
    }

    protected function resolveClockIssues(Collection $userIds, array $payload): array
    {
        $fromDate = isset($payload['from_date']) ? Carbon::parse($payload['from_date'])->startOfDay() : null;
        $toDate = isset($payload['to_date']) ? Carbon::parse($payload['to_date'])->endOfDay() : null;

        $issuesQuery = ClockInOut::query()
            ->whereIn('user_id', $userIds)
            ->where('is_issue', true);

        if ($fromDate) {
            $issuesQuery->where('clock_in', '>=', $fromDate);
        }

        if ($toDate) {
            $issuesQuery->where('clock_in', '<=', $toDate);
        }

        $issueIds = $issuesQuery->pluck('id');
        $resolvedCount = $issueIds->count();

        if ($resolvedCount > 0) {
            ClockInOut::query()->whereIn('id', $issueIds)->update(['is_issue' => false]);
        }

        return [
            'resolved_issues' => $resolvedCount,
            'clock_ids' => $issueIds,
        ];
    }

    protected function recomputeDurations(Collection $userIds, array $payload): array
    {
        $fromDate = isset($payload['from_date']) ? Carbon::parse($payload['from_date'])->startOfDay() : null;
        $toDate = isset($payload['to_date']) ? Carbon::parse($payload['to_date'])->endOfDay() : null;

        $query = ClockInOut::query()
            ->whereIn('user_id', $userIds)
            ->whereNotNull('clock_in')
            ->whereNotNull('clock_out');

        if ($fromDate) {
            $query->where('clock_in', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('clock_in', '<=', $toDate);
        }

        $updated = 0;
        $mismatched = 0;

        $query->orderBy('id')->chunkById(100, function ($clocks) use (&$updated, &$mismatched) {
            foreach ($clocks as $clock) {
                $clockInAt = Carbon::parse($clock->clock_in);
                $clockOutAt = Carbon::parse($clock->clock_out);

                if ($clockOutAt->lessThanOrEqualTo($clockInAt)) {
                    $mismatched++;
                    continue;
                }

                $duration = $clockInAt->diff($clockOutAt)->format('%H:%I:%S');

                if ($clock->duration !== $duration) {
                    $clock->update(['duration' => $duration]);
                    $updated++;
                }
            }
        });

        return [
            'recomputed' => $updated,
            'skipped_invalid' => $mismatched,
        ];
    }
}
