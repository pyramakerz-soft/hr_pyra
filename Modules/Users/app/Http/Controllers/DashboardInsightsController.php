<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Models\ServiceAction;
use Modules\Clocks\Models\UserClockOvertime;
use Modules\Users\Enums\StatusEnum;
use Modules\Users\Models\Excuse;
use Modules\Users\Models\SystemNotification;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;

class DashboardInsightsController extends Controller
{
    use ResponseTrait;

    public function summary()
    {
        $pendingIssues = ClockInOut::query()->where('is_issue', true)->count();

        $openClocks = ClockInOut::query()
            ->whereNull('clock_out')
            ->count();

        $pendingExcuses = Excuse::query()
            ->where('status', StatusEnum::Pending)
            ->count();

        $pendingOvertime = UserClockOvertime::query()
            ->where(function ($query) {
                $query->whereNull('approval_of_direct')
                    ->orWhereNull('approval_of_head')
                    ->orWhere('approval_of_direct', 'pending')
                    ->orWhere('approval_of_head', 'pending');
            })
            ->count();

        $latestNotifications = SystemNotification::query()
            ->select(['id', 'title', 'type', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentServiceActions = ServiceAction::query()
            ->select(['id', 'action_type', 'status', 'scope_type', 'created_at'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->returnData('summary', [
            'metrics' => [
                'pending_issues' => $pendingIssues,
                'open_clocks' => $openClocks,
                'pending_excuses' => $pendingExcuses,
                'pending_overtime' => $pendingOvertime,
            ],
            'notifications' => $latestNotifications,
            'service_actions' => $recentServiceActions,
        ]);
    }

    public function presence(Request $request)
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::now()->startOfDay();

        $startOfDay = $date->copy();
        $endOfDay = $date->copy()->endOfDay();

        $totalEmployees = User::query()->count();

        $presentIds = ClockInOut::query()
            ->whereBetween('clock_in', [$startOfDay, $endOfDay])
            ->pluck('user_id')
            ->unique();

        $onLeaveIds = UserVacation::query()
            ->where(function ($query) use ($startOfDay, $endOfDay) {
                $query->whereDate('from_date', '<=', $endOfDay)
                    ->whereDate('to_date', '>=', $startOfDay);
            })
            ->where('status', StatusEnum::Approved)
            ->pluck('user_id')
            ->unique();

        $present = $presentIds->count();
        $onLeave = $onLeaveIds->count();
        $absent = max($totalEmployees - $present - $onLeave, 0);

        $openClocks = ClockInOut::query()
            ->whereBetween('clock_in', [$startOfDay, $endOfDay])
            ->whereNull('clock_out')
            ->count();

        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $trendDate = $date->copy()->subDays($i);
            $trendStart = $trendDate->copy()->startOfDay();
            $trendEnd = $trendDate->copy()->endOfDay();

            $count = ClockInOut::query()
                ->whereBetween('clock_in', [$trendStart, $trendEnd])
                ->distinct('user_id')
                ->count('user_id');

            $trend[] = [
                'date' => $trendDate->toDateString(),
                'present' => $count,
            ];
        }

        return $this->returnData('presence', [
            'date' => $date->toDateString(),
            'totals' => [
                'employees' => $totalEmployees,
                'present' => $present,
                'absent' => $absent,
                'on_leave' => $onLeave,
                'open_clocks' => $openClocks,
            ],
            'trend' => $trend,
        ]);
    }
}
