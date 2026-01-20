<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Users\Enums\NotificationType;
use Modules\Users\Http\Requests\StoreNotificationRequest;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\SystemNotification;
use Modules\Users\Models\SystemNotificationRecipient;
use Modules\Users\Models\User;

class NotificationController extends Controller
{
    use ResponseTrait;

    public function types()
    {
        return $this->returnData('types', NotificationType::labels());
    }

    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 25);
        $limit = max(1, min(100, $limit));
        $isNotRead = $request->boolean('is_not_read', false);

        $notifications = SystemNotification::query()
            ->with(['createdBy:id,name', 'recipients' => fn($q) => $q->where('user_id', Auth::id())])
            ->whereHas('recipients', fn(Builder $builder) => $builder->where('user_id', Auth::id()))
            ->when($request->filled('type'), fn(Builder $builder) => $builder->where('type', $request->string('type')))
            ->when($request->filled('scope_type'), fn(Builder $builder) => $builder->where('scope_type', $request->string('scope_type')))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                $recipient = $notification->recipients->first();
                $notification->read_at = $recipient ? $recipient->read_at : null;
                $notification->status = $recipient ? $recipient->status : null;
                unset($notification->recipients);
                return $notification;
            })
            ->when($isNotRead, fn($collection) => $collection->filter(fn($notification) => $notification->read_at === null))
            ->values();

        return $this->returnData('notifications', $notifications);
    }

    public function show(SystemNotification $notification)
    {
        $notification->load([
            'createdBy:id,name',
            'recipients.user:id,name',
        ]);

        return $this->returnData('notification', $notification);
    }

    public function store(StoreNotificationRequest $request)
    {
        $validated = $request->validated();
        $filters = $validated['filters'] ?? [];

        $recipientIds = $this->resolveRecipients(
            $validated['scope_type'],
            $validated['scope_id'] ?? null,
            $validated['user_ids'] ?? [],
            $filters
        );

        if ($recipientIds->isEmpty()) {
            return $this->returnError('No employees matched the selected filters.');
        }

        $notification = new SystemNotification([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'scope_type' => $validated['scope_type'],
            'scope_id' => $validated['scope_id'] ?? null,
            'filters' => $filters,
            'created_by' => Auth::id(),
            'scheduled_at' => isset($validated['scheduled_at'])
                ? Carbon::parse($validated['scheduled_at'])
                : null,
        ]);

        DB::transaction(function () use ($notification, $recipientIds) {
            $notification->save();

            $rows = $recipientIds->map(function (int $userId) use ($notification) {
                $now = Carbon::now();

                return [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'status' => 'sent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            SystemNotificationRecipient::query()->insert($rows);
        });

        return $this->returnData(
            'notification',
            $notification->loadCount('recipients as recipients_count'),
            'Notification queued successfully.'
        );
    }

    public function markRead(Request $request, SystemNotification $notification)
    {
        $userId = Auth::id();

        if (!$userId) {
            return $this->returnError('Unable to determine which employee should be updated.');
        }
        $recipient = SystemNotificationRecipient::query()
            ->where('notification_id', $notification->id)
            ->where('user_id', $userId)
            ->first();

        if (!$recipient) {
            return $this->returnError('No notification record found for the supplied employee.');
        }

        if (!$recipient->read_at) {
            $recipient->forceFill([
                'status' => 'read',
                'read_at' => Carbon::now(),
            ])->save();
        }

        return $this->returnSuccessMessage('Notification marked as read.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    protected function resolveRecipients(string $scopeType, ?int $scopeId, array $userIds, array $filters): Collection
    {
        $query = match ($scopeType) {
            'department' => $this->usersForDepartment($scopeId),
            'sub_department' => $this->usersForSubDepartment($scopeId),
            'user' => User::query()->where('id', $scopeId),
            'custom' => User::query()->whereIn('id', $userIds),
            'all' => User::query(),
            default => User::query()->whereRaw('1 = 0'),
        };

        $query = $this->applyFilters($query, $filters);

        return $query->pluck('id')->unique()->values();
    }

    /**
     * @param array<string, mixed> $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['roles']) && is_array($filters['roles'])) {
            $roles = array_filter($filters['roles'], fn($role) => is_string($role) && $role !== '');

            if (!empty($roles)) {
                $query->whereHas('roles', fn(Builder $builder) => $builder->whereIn('name', $roles));
            }
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function usersForDepartment(?int $departmentId): Builder
    {
        if (!$departmentId) {
            return User::query()->whereRaw('1 = 0');
        }

        $department = Department::findOrFail($departmentId);
        $subDepartmentIds = SubDepartment::query()->where('department_id', $department->id)->pluck('id');

        return User::query()->where(function (Builder $builder) use ($department, $subDepartmentIds) {
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
        if (!$subDepartmentId) {
            return User::query()->whereRaw('1 = 0');
        }

        SubDepartment::findOrFail($subDepartmentId);

        return User::query()->where('sub_department_id', $subDepartmentId);
    }
}
