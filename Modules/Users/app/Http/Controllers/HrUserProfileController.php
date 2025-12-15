<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Modules\Users\Http\Requests\Hr\UpdateHrUserProfileRequest;
use Modules\Users\Models\User;
use Modules\Users\Models\UserDetail;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;

class HrUserProfileController extends Controller
{
    use ResponseTrait;

    public function show(User $user)
    {
        $user->loadMissing([
            'user_detail',
            'department:id,name',
            'subDepartment:id,name',
            'vacationBalances.vacationType:id,name,default_days',
        ]);

        $vacationTypes = VacationType::orderBy('name')
            ->get(['id', 'name', 'default_days']);

        return $this->returnData('profile', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'code' => $user->code,
                'department' => $user->department?->name,
                'sub_department' => $user->subDepartment?->name,
            ],
            'detail' => [
                'salary' => optional($user->user_detail)->salary,
                'hourly_rate' => optional($user->user_detail)->hourly_rate,
                'overtime_hourly_rate' => optional($user->user_detail)->overtime_hourly_rate,
                'working_hours_day' => optional($user->user_detail)->working_hours_day,
                'overtime_hours' => optional($user->user_detail)->overtime_hours,
                'start_time' => optional($user->user_detail)->start_time,
                'end_time' => optional($user->user_detail)->end_time,
                'emp_type' => optional($user->user_detail)->emp_type,
                'hiring_date' => optional($user->user_detail)->hiring_date,
            ],
            'vacation_balances' => $user->vacationBalances->map(function (UserVacationBalance $balance) {
                return [
                    'id' => $balance->id,
                    'vacation_type_id' => $balance->vacation_type_id,
                    'vacation_type_name' => $balance->vacationType?->name,
                    'year' => $balance->year,
                    'allocated_days' => (float) ($balance->allocated_days ?? 0),
                    'used_days' => (float) ($balance->used_days ?? 0),
                    'remaining_days' => (float) $balance->remaining_days,
                ];
            })->values(),
            'vacation_types' => $vacationTypes,
        ], 'HR profile data retrieved successfully');
    }

    public function update(UpdateHrUserProfileRequest $request, User $user)
    {
        $payload = $request->validated();

        DB::transaction(function () use ($payload, $user) {
            $detailData = $payload['detail'] ?? [];
            if (!empty($detailData)) {
                $detail = $user->user_detail ?? new UserDetail(['user_id' => $user->id]);
                $detail->fill([
                    'salary' => $detailData['salary'] ?? $detail->salary,
                    'hourly_rate' => $detailData['hourly_rate'] ?? $detail->hourly_rate,
                    'overtime_hourly_rate' => $detailData['overtime_hourly_rate'] ?? $detail->overtime_hourly_rate,
                    'working_hours_day' => $detailData['working_hours_day'] ?? $detail->working_hours_day,
                    'overtime_hours' => $detailData['overtime_hours'] ?? $detail->overtime_hours,
                    'start_time' => $detailData['start_time'] ?? $detail->start_time,
                    'end_time' => $detailData['end_time'] ?? $detail->end_time,
                    'emp_type' => $detailData['emp_type'] ?? $detail->emp_type,
                    'hiring_date' => $detailData['hiring_date'] ?? $detail->hiring_date,
                ]);
                $detail->user_id = $user->id;
                $detail->save();
            }

            $balances = $payload['vacation_balances'] ?? [];
            foreach ($balances as $balanceData) {
                $id = $balanceData['id'] ?? null;
                $year = (int) ($balanceData['year'] ?? now()->year);
                $allocated = (float) ($balanceData['allocated_days'] ?? 0);
                $used = (float) ($balanceData['used_days'] ?? 0);

                if ($id) {
                    $balance = UserVacationBalance::where('user_id', $user->id)->findOrFail($id);
                } else {
                    $balance = UserVacationBalance::firstOrNew([
                        'user_id' => $user->id,
                        'vacation_type_id' => $balanceData['vacation_type_id'],
                        'year' => $year,
                    ]);
                }

                $balance->allocated_days = $allocated;
                $balance->used_days = $used;
                $balance->year = $year;
                $balance->vacation_type_id = $balanceData['vacation_type_id'] ?? $balance->vacation_type_id;
                $balance->save();
            }

            $idsToDelete = $payload['vacation_balance_ids_to_delete'] ?? [];
            if (!empty($idsToDelete)) {
                UserVacationBalance::where('user_id', $user->id)
                    ->whereIn('id', $idsToDelete)
                    ->delete();
            }
        });

        $user->refresh();

        return $this->show($user);
    }
}

