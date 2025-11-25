<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Clocks\Models\UserClockOvertime;

class UserClockOvertimeController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $manager = Auth::user();
        $employeeIds = $manager->getManagedEmployeeIds();

        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }

        $query = UserClockOvertime::with(['user', 'clock'])
            ->whereIn('user_id', $employeeIds);

        $searchTerm = $request->query('searchTerm');
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $statusFilter = $request->query('status');
        if (!empty($statusFilter)) {
            $statuses = is_array($statusFilter) ? $statusFilter : [$statusFilter];
            $statuses = array_filter($statuses, fn($status) => $status !== 'all');

            if (!empty($statuses)) {
                $query->where(function ($outer) use ($statuses) {
                    foreach ($statuses as $status) {
                        $status = strtolower($status);
                        $outer->orWhere(function ($inner) use ($status) {
                            if ($status === 'approved') {
                                $inner->where('approval_of_direct', 'approved')
                                    ->where('approval_of_head', 'approved');
                            } elseif ($status === 'declined') {
                                $inner->where(function ($decline) {
                                    $decline->where('approval_of_direct', 'declined')
                                        ->orWhere('approval_of_head', 'declined');
                                });
                            } elseif ($status === 'pending') {
                                $inner->where('approval_of_direct', '!=', 'declined')
                                    ->where('approval_of_head', '!=', 'declined')
                                    ->where(function ($pending) {
                                        $pending->where('approval_of_direct', 'pending')
                                            ->orWhere('approval_of_head', 'pending');
                                    });
                            }
                        });
                    }
                });
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('overtime_date', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('overtime_date', '<=', $request->query('to_date'));
        }

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }

        $overtimes = $query->orderByDesc('overtime_date')
            ->paginate($perPage, ['*'], 'page', $request->query('page', 1));

        $formatted = collect($overtimes->items())->map(function (UserClockOvertime $overtime) {
            return [
                'overtime' => $overtime,
                'user' => $overtime->user,
                'clock' => $overtime->clock,
            ];
        });

        return $this->returnData('Overtimes', [
            'data' => $formatted,
            'pagination' => [
                'total' => $overtimes->total(),
                'per_page' => $overtimes->perPage(),
                'current_page' => $overtimes->currentPage(),
                'last_page' => $overtimes->lastPage(),
                'next_page_url' => $overtimes->nextPageUrl(),
                'prev_page_url' => $overtimes->previousPageUrl(),
            ],
        ], 'Overtime entries for employees managed by the authenticated user');
    }

    public function updateStatus(UserClockOvertime $overtime, Request $request)
    {
        $manager = Auth::user();
        $request->validate([
            'status' => 'required|in:approved,declined',
            'approver' => 'nullable|in:direct,head',
        ]);

        $employeeIds = $manager->getManagedEmployeeIds();
        if (!$employeeIds->contains($overtime->user_id)) {
            return $this->returnError('You are not authorized to update this overtime entry', 403);
        }

        $role = strtolower($manager->getRoleName() ?? '');
        $approver = $request->input('approver');
        if (!$approver) {
            $approver = $role === 'team leader' ? 'direct' : 'head';
        }

        $status = $request->input('status');

        if ($approver === 'direct') {
            if (!in_array($role, ['team leader', 'admin'])) {
                return $this->returnError('You do not have permission to approve as direct manager', 403);
            }

            $overtime->approval_of_direct = $status;
            $overtime->direct_approved_by = $manager->id;
        } elseif ($approver === 'head') {
            if (!in_array($role, ['manager', 'hr', 'admin'])) {
                return $this->returnError('You do not have permission to approve as head manager', 403);
            }

            $overtime->approval_of_head = $status;
            $overtime->head_approved_by = $manager->id;
        } else {
            return $this->returnError('Invalid approver type provided', 422);
        }

        $overtime->save();

        return $this->returnData('Overtime', $overtime->fresh(['user', 'clock']), 'Overtime status updated successfully');
    }
}
