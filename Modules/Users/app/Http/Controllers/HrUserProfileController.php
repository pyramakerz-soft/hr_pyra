<?php

namespace Modules\Users\Http\Controllers;

use Modules\Users\Http\Controllers\UserVacationController;
use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;
use Modules\Users\Http\Requests\Hr\UpdateHrUserProfileRequest;
use Modules\Users\Models\User;
use Modules\Users\Models\UserDetail;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;
use Modules\Users\Http\Requests\Hr\BulkUpdateTimeRequest;
use Carbon\Carbon;

class HrUserProfileController extends Controller
{
    use ResponseTrait;

    const ANNUAL_LEAVE_NAME = 'Annual Leave';
    const CASUAL_LEAVE_NAME = 'Casual Leave';
    const EMERGENCY_LEAVE_NAME = 'Emergency Leave';
    const UNPAID_LEAVE_NAME = 'Unpaid Leave';
    const MATERNITY_LEAVE_NAME = 'Maternity Leave';
    const MARRIAGE_LEAVE_NAME = 'Marriage Leave';
    const HAJJ_LEAVE_NAME = 'Hajj / Umrah Leave';
    const EXCEPTIONAL_LEAVE_NAME = 'Exceptional Leave';
    const SICK_LEAVE_NAME = 'Sick Leave';

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
            'vacation_balances' => $user->vacationBalances
                ->filter(fn(UserVacationBalance $balance) => $balance->vacationType?->name !== 'Exceptional Leave')
                ->map(function (UserVacationBalance $balance) {
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

        $validationError = $this->validateVacationBalances($user, $payload);
        if ($validationError) {
            return $this->returnError($validationError, 422);
        }

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
            $annualBalanceAdjustment = 0; // Track adjustment from Casual/Emergency/Exceptional leaves
            $annualBalanceYear = null;

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
                $previouslyUsed = (float) ($balance->used_days ?? 0);
                $balance->used_days = $used;
                $balance->year = $year;
                $balance->vacation_type_id = $balanceData['vacation_type_id'] ?? $balance->vacation_type_id;
                $vacation = VacationType::where('id', $balance->vacation_type_id)->first();

                // Accumulate adjustments for annual balance from Casual/Emergency/Exceptional leaves
                if ($vacation && in_array($vacation->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME, self::EXCEPTIONAL_LEAVE_NAME])) {
                    $annualBalanceAdjustment += ($used - $previouslyUsed);
                    $annualBalanceYear = $year;
                }
                $balance->save();
            }

            // Apply accumulated adjustment to Annual Leave balance after all balances are saved
            if ($annualBalanceAdjustment != 0 && $annualBalanceYear !== null) {
                $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
                if ($annualType) {
                    $annualBalance = UserVacationController::getOrCreateBalance($user, $annualType, Carbon::createFromDate($annualBalanceYear));
                    $annualBalance->used_days = max(0, ($annualBalance->used_days ?? 0) + $annualBalanceAdjustment);
                    $annualBalance->save();
                }
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

    private function validateVacationBalances(User $user, array $payload): ?string
    {
        $balances = $payload['vacation_balances'] ?? [];
        $idsToDelete = $payload['vacation_balance_ids_to_delete'] ?? [];

        $vacationTypes = VacationType::whereIn('name', [
            self::ANNUAL_LEAVE_NAME,
            self::CASUAL_LEAVE_NAME,
            self::EMERGENCY_LEAVE_NAME
        ])->get()->keyBy('name');

        $types = [
            'annual' => $vacationTypes[self::ANNUAL_LEAVE_NAME]?->id,
            'casual' => $vacationTypes[self::CASUAL_LEAVE_NAME]?->id,
            'emergency' => $vacationTypes[self::EMERGENCY_LEAVE_NAME]?->id,
        ];

        // Group processing years from payload
        $yearsToCheck = [];
        foreach ($balances as $balanceData) {
            $year = (int) ($balanceData['year'] ?? now()->year);
            $typeId = $balanceData['vacation_type_id'] ?? null;
            
            if (!$typeId && isset($balanceData['id'])) {
                $typeId = UserVacationBalance::where('id', $balanceData['id'])->value('vacation_type_id');
            }

            // Only check years where Annual, Casual or Emergency balances are being modified
            if (in_array($typeId, $types)) {
                $yearsToCheck[$year] = true;
            }
        }

        foreach (array_keys($yearsToCheck) as $year) {
            $amounts = [];
            foreach ($types as $key => $typeId) {
                if (!$typeId) {
                    $amounts[$key] = 0;
                    continue;
                }

                $foundInPayload = false;
                foreach ($balances as $balanceData) {
                    $balanceTypeId = $balanceData['vacation_type_id'] ?? null;
                    if (!$balanceTypeId && isset($balanceData['id'])) {
                        $balanceTypeId = UserVacationBalance::where('id', $balanceData['id'])->value('vacation_type_id');
                    }

                    if ($balanceTypeId == $typeId && (int) ($balanceData['year'] ?? now()->year) == $year) {
                        $amounts[$key] = (float) ($balanceData['allocated_days'] ?? 0);
                        $foundInPayload = true;
                        break;
                    }
                }

                if (!$foundInPayload) {
                    $dbBalance = UserVacationBalance::where('user_id', $user->id)
                        ->where('vacation_type_id', $typeId)
                        ->where('year', $year)
                        ->first();

                    if ($dbBalance && !in_array($dbBalance->id, $idsToDelete)) {
                        $amounts[$key] = (float) ($dbBalance->allocated_days ?? 0);
                    } else {
                        $amounts[$key] = 0;
                    }
                }
            }

            if (round($amounts['casual'] + $amounts['emergency'], 2) != round($amounts['annual'], 2)) {
                return "For year $year: Casual Leave ({$amounts['casual']}) + Emergency Leave ({$amounts['emergency']}) must equal Annual Leave ({$amounts['annual']}).";
            }
        }

        return null;
    }

    public function bulkUpdateTime(BulkUpdateTimeRequest $request)
    {
        $payload = $request->validated();
        $departmentId = $payload['department_id'] ?? null;
        $subDepartmentId = $payload['sub_department_id'] ?? null;
        $startTime = $payload['start_time'] ?? null;
        $endTime = $payload['end_time'] ?? null;
        $workingHours = $payload['working_hours_day'] ?? null;

        $query = User::query();

        if ($subDepartmentId) {
            $query->where('sub_department_id', $subDepartmentId);
        } elseif ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $userIds = $query->pluck('id');

        if ($userIds->isEmpty()) {
            return $this->returnError('No employees found for the selected criteria', 404);
        }

        DB::transaction(function () use ($userIds, $startTime, $endTime, $workingHours) {
            foreach ($userIds as $userId) {
                $detail = UserDetail::firstOrNew(['user_id' => $userId]);
                
                if ($startTime) {
                    $detail->start_time = $startTime;
                }
                
                if ($endTime) {
                    $detail->end_time = $endTime;
                }

                if ($workingHours) {
                    $detail->working_hours_day = $workingHours;
                }
                
                $detail->save();
            }
        });

        return $this->returnSuccessMessage('Employee times updated successfully');
    }
}

