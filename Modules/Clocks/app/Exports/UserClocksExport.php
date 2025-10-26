<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Clocks\Exports\Sheets\UserClocksAggregatedSheet;
use Modules\Clocks\Exports\Sheets\UserClocksDetailedSheet;
use Modules\Clocks\Exports\Sheets\UserClocksSummarySheet;
use Modules\Clocks\Exports\Sheets\UserClocksPlanSheet;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Models\UserClockOvertime;
use Modules\Users\Models\User;
use Modules\Users\Models\CustomVacation;
use Modules\Clocks\Support\DeductionPlanResolver;
use Modules\Clocks\Support\DeductionRuleEngine;

class UserClocksExport implements WithMultipleSheets
{
    use Exportable;

    protected Collection $users;
    protected $startDate;
    protected $endDate;

    protected Collection $detailedRows;
    protected Collection $summaryRows;
    protected Collection $planRows;
    protected Collection $aggregatedRows;
    protected array $rowStyles = [];
    protected array $ruleLegend = [];
    protected array $summaryRowComparisons = [];
    protected array $defaultCategoryColors = [
        'lateness' => 'F8D5D8',
        'deduction' => 'F7E0C7',
        'shortfall' => 'F3E5AB',
        'default_shortfall' => 'F9D9B5',
        'overtime' => 'C8E6C9',
        'vacation' => 'AED9F4',
        'issue' => 'F6C1D1',
        'bonus' => 'D7C7ED',
        'other' => 'D0D7DD',
    ];
    protected array $excuseStatusOrder = ['approved', 'pending', 'rejected'];

    public function __construct($users, $startDate = null, $endDate = null)
    {
        $this->users = $this->normalizeUsers($users);
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->planRows = collect();
        $this->aggregatedRows = collect();
        $this->ruleLegend = [];

        $this->prepareData();
    }

    public function sheets(): array
    {
        return [
            new UserClocksSummarySheet($this->summaryRows, [], $this->summaryRowComparisons),
            new UserClocksPlanSheet($this->planRows, $this->ruleLegend),
            new UserClocksDetailedSheet($this->detailedRows, $this->rowStyles),
            new UserClocksAggregatedSheet($this->aggregatedRows),
        ];
    }

    public function getSummaryRows(): Collection
    {
        return $this->summaryRows;
    }

    public function getDetailedRows(): Collection
    {
        return $this->detailedRows;
    }

    public function getAggregatedRows(): Collection
    {
        return $this->aggregatedRows;
    }

    public function getRowStyles(): array
    {
        return $this->rowStyles;
    }

    public function getSummaryComparisons(): array
    {
        return $this->summaryRowComparisons;
    }

    protected function prepareData(): void
    {
        $this->detailedRows = collect();
        $this->summaryRows = collect();
        $this->planRows = collect();
        $this->aggregatedRows = collect();
        $this->rowStyles = [];
        $this->summaryRowComparisons = [];
        $this->ruleLegend = [
            'deduction' => $this->defaultCategoryColors['deduction'],
            'overtime' => $this->defaultCategoryColors['overtime'],
            'vacation' => $this->defaultCategoryColors['vacation'],
            'issue' => $this->defaultCategoryColors['issue'],
            'other' => $this->defaultCategoryColors['other'],
        ];

        $planResolver = new DeductionPlanResolver();

        $overallTotals = [
            'required_minutes' => 0,
            'worked_minutes' => 0,
            'recorded_ot_minutes' => 0,
            'issue_days' => 0,
            'attendance_ot_minutes' => 0,
            'raw_deduction_minutes' => 0,
            'excuse_minutes' => 0,
            'chargeable_deduction_minutes' => 0,
            'vacation_days' => 0,
            'base_salary' => 0.0,
            'worked_pay' => 0.0,
            'ot_pay' => 0.0,
            'gross_pay' => 0.0,
            'deduction_amount' => 0.0,
            'net_pay' => 0.0,
            'excuse_tokens_used' => 0,
            'lateness_beyond_grace_minutes' => 0,
            'plan_monetary_amount' => 0.0,
            'worked_days' => 0,
            'approved_excuse_count' => 0,
            'approved_excuse_minutes' => 0,
            'pending_excuse_count' => 0,
            'pending_excuse_minutes' => 0,
            'rejected_excuse_count' => 0,
            'rejected_excuse_minutes' => 0,
            'vacation_days_left' => 0.0,
        ];

        $customVacationCache = [];

        foreach ($this->users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $user->loadMissing(['work_types', 'subDepartment', 'user_detail', 'department']);
            $userWorkTypes = $user->work_types
                ->pluck('name')
                ->map(function ($name) {
                    return is_string($name) ? strtolower($name) : $name;
                })
                ->filter()
                ->values()
                ->all();
            $isPartTime = (bool) ($user->is_part_time ?? false);
            if (! $isPartTime) {
                $isPartTime = in_array('part time', $userWorkTypes, true);
            }

            $resolvedPlan = $planResolver->resolveForUser($user);
            $deductionEngine = new DeductionRuleEngine($resolvedPlan);
            $planGraceMinutes = $resolvedPlan['grace_minutes'] ?? 15;
            $ruleState = [];
            $totalPlanMonetaryAmount = 0.0;
            $userPlanRowStart = $this->planRows->count();

            foreach ($resolvedPlan['rules'] as $planRule) {
                $categoryKey = $planRule['category'] ?? 'other';
                $color = ltrim($planRule['color'] ?? '', '#');
                if ($color !== '') {
                    $this->ruleLegend[$categoryKey] = strtoupper($color);
                }
            }

            $userTimezoneName = optional($user->timezone)->name ?? 'Africa/Cairo';
            $department = optional($user->department);
            $subDepartment = optional($user->subDepartment);
            $workScheduleType = strtolower($department->work_schedule_type ?? 'flexible');
            $isFlexibleSchedule = $workScheduleType !== 'strict';
            $userDetail = optional($user->user_detail);
            $worksOnSaturday = (bool) ($department?->works_on_saturday ?? false);
            if ($subDepartment && $subDepartment->works_on_saturday !== null) {
                $worksOnSaturday = (bool) $subDepartment->works_on_saturday;
            }
            if ($userDetail && $userDetail->works_on_saturday !== null) {
                $worksOnSaturday = (bool) $userDetail->works_on_saturday;
            }
            $workingHoursDay = (float) ($userDetail->working_hours_day ?? 8);
            if ($workingHoursDay <= 0) {
                $workingHoursDay = 8;
            }
            $requiredMinutesPerDay = (int) round($workingHoursDay * 60);
            $scheduledStartTime = $userDetail->start_time
                ?? optional($user->subDepartment)->flexible_start_time
                ?? ($department?->flexible_start_time ?? null)
                ?? ($department?->start_time ?? null)
                ?? '08:00:00';

            $now = Carbon::now();
            $defaultStartDate = $now->copy()->subMonth()->day(26)->startOfDay();
            $defaultEndDate = $now->copy()->day(26)->endOfDay();

            $startDate = $this->startDate
                ? Carbon::parse($this->startDate, $userTimezoneName)->startOfDay()->setTimezone('UTC')
                : $defaultStartDate->copy();
            $endDate = $this->endDate
                ? Carbon::parse($this->endDate, $userTimezoneName)->endOfDay()->setTimezone('UTC')
                : $defaultEndDate->copy();

            $userRangeStart = $startDate->copy()->setTimezone($userTimezoneName)->toDateString();
            $userRangeEnd = $endDate->copy()->setTimezone($userTimezoneName)->toDateString();

            $approvedVacations = $user->user_vacations()
                ->where('status', 'approved')
                ->whereDate('from_date', '<=', $userRangeEnd)
                ->whereDate('to_date', '>=', $userRangeStart)
                ->with('vacationType')
                ->get();

            $requestedVacationsByDate = [];
            foreach ($approvedVacations as $vacation) {
                $fromDate = Carbon::parse($vacation->from_date)->startOfDay();
                $toDate = Carbon::parse($vacation->to_date)->startOfDay();

                for ($vacDate = $fromDate->copy(); $vacDate->lte($toDate); $vacDate->addDay()) {
                    $requestedVacationsByDate[$vacDate->toDateString()][] = $vacation;
                }
            }

            $customVacationsByDate = [];
            $customVacationCacheKey = implode('|', [
                $user->department_id ?? 'null',
                $user->sub_department_id ?? 'null',
                $userRangeStart,
                $userRangeEnd,
            ]);

            if (! array_key_exists($customVacationCacheKey, $customVacationCache)) {
                if (! $user->department_id && ! $user->sub_department_id) {
                    $customVacationCache[$customVacationCacheKey] = collect();
                } else {
                    $query = CustomVacation::query()
                        ->betweenDates(Carbon::parse($userRangeStart), Carbon::parse($userRangeEnd));

                    $hasConstraint = false;
                    if ($user->sub_department_id) {
                        $query->whereHas('subDepartments', function ($subQuery) use ($user) {
                            $subQuery->where('sub_departments.id', $user->sub_department_id);
                        });
                        $hasConstraint = true;
                    }

                    if ($user->department_id) {
                        $method = $hasConstraint ? 'orWhereHas' : 'whereHas';
                        $query->{$method}('departments', function ($deptQuery) use ($user) {
                            $deptQuery->where('departments.id', $user->department_id);
                        });
                        $hasConstraint = true;
                    }

                    $customVacationCache[$customVacationCacheKey] = $hasConstraint
                        ? $query->get()
                        : collect();
                }
            }

            $customVacations = $customVacationCache[$customVacationCacheKey];
            foreach ($customVacations as $customVacation) {
                $fromDate = $customVacation->start_date->copy();
                $toDate = $customVacation->end_date->copy();

                for ($vacDate = $fromDate->copy(); $vacDate->lte($toDate); $vacDate->addDay()) {
                    $customVacationsByDate[$vacDate->toDateString()][] = $customVacation;
                }
            }

            $vacationBalanceYear = Carbon::parse($userRangeEnd)->year;
            $vacationDaysLeft = $user->vacationBalances()
                ->where('year', $vacationBalanceYear)
                ->get()
                ->sum(function ($balance) {
                    return (float) ($balance->remaining_days ?? 0);
                });
            $overallTotals['vacation_days_left'] += $vacationDaysLeft;

            $clocks = $user->user_clocks()
                ->whereBetween('clock_in', [$startDate, $endDate])
                ->orderBy('clock_in')
                ->get();

            $overtimeRecords = UserClockOvertime::where('user_id', $user->id)
                ->whereBetween('overtime_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->keyBy(function ($record) {
                    return Carbon::parse($record->overtime_date)->toDateString();
                });

            $grouped = $clocks->groupBy(function ($clock) use ($userTimezoneName) {
                return Carbon::parse($clock->clock_in, $userTimezoneName)
                    ->format('Y-m-d');
            });

            $dailyEntries = [];
            $totalRequiredMinutes = 0;
            $totalWorkedMinutes = 0;
            $totalRecordedOtMinutes = 0;
            $totalAttendanceOtMinutes = 0;
            $totalRawDeductionMinutes = 0;
            $totalExcuseMinutesApplied = 0;
            $totalChargeableDeductionMinutes = 0;
            $totalVacationDays = 0;
            $totalLatenessBeyondGrace = 0;
            $totalApprovedExcuseMinutes = 0;
            $totalIssueDays = 0;
            $totalWorkedDays = 0;

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $localDate = $date->copy()->setTimezone($userTimezoneName);
                $formattedDate = $localDate->format('Y-m-d');
                $dailyClocks = $grouped->get($formattedDate, collect());
                $requestedForDay = $requestedVacationsByDate[$formattedDate] ?? [];
                $customForDay = $customVacationsByDate[$formattedDate] ?? [];
                $hasRequestedVacation = ! empty($requestedForDay);
                $hasCustomVacation = ! empty($customForDay);

                if ($hasRequestedVacation) {
                    $totalVacationDays++;
                }

                $vacationTags = [];
                foreach ($requestedForDay as $vacation) {
                    $typeName = optional($vacation->vacationType)->name;
                    $vacationTags[] = $typeName
                        ? 'Requested: ' . $typeName
                        : 'Requested Vacation';
                }

                foreach ($customForDay as $vacation) {
                    $vacationTags[] = 'Custom: ' . $vacation->name;
                }

                if ($vacationTags) {
                    $vacationTags = array_values(array_unique($vacationTags));
                }

                $vacationLabel = $vacationTags ? implode(' | ', $vacationTags) : 'NO';
                $isVacation = $hasRequestedVacation || $hasCustomVacation;

                // Handle days without clock entries
                if ($dailyClocks->isEmpty()) {
                    // Add entry for days without clock data
                    $dailyEntries[] = [
                        'row_data' => [
                            'Date' => $formattedDate,
                            'Name' => $user->name,
                            'Clock In' => '',
                            'Clock Out' => '',
                            'Code' => $user->code,
                            'Department' => $user->department?->name ?? 'N/A',
                            'Total Hours in That Day' => '00:00',
                            'Total Over time in That Day' => '00:00',
                            'Plan Deduction in That Day' => '00:00',
                            'Deduction Details' => '',
                            'Excuse Deducted in That Day' => '00:00',
                            'Excuse Remaining (Policy 4h)' => '',
                            'Total Excuses in That Day' => '00:00',
                            'Is this date has vacation' => $vacationLabel,
                            'Location In' => '',
                            'Location Out' => '',
                            'Attendance Over time in That Day' => '00:00',
                        ],
                        'segments' => [],
                        'weekend' => $date->isFriday() || $date->isSaturday(),
                        'ot_status' => null,
                        'raw_deduction_minutes' => 0,
                        'excuse_applied_minutes' => 0,
                        'chargeable_deduction_minutes' => 0,
                        'deduction_rules' => [],
                        'deduction_detail' => '',
                        'plan_monetary_amount' => 0,
                        'deduction_color' => null,
                        'is_vacation' => $isVacation,
                        'issue_columns' => [],
                    ];
                    continue;
                }

                $hasIssue = false;
                $issueColumns = [];
                $workedMinutes = 0;
                $earliestIn = null;
                $latestOut = null;
                $firstLocIn = '';
                $lastLocOut = '';
                $segments = [];
                $baselineClockForSchedule = null;

                if ($dailyClocks->isNotEmpty()) {
                    foreach ($dailyClocks as $clock) {
                        $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in, 'UTC') : null;
                        $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out, 'UTC') : null;

                        if ($clockIn && $clockOut) {
                            $diff = $clockIn->diffInMinutes($clockOut);
                            $workedMinutes += $diff;
                        }

                        if (! $clockIn) {
                            $hasIssue = true;
                            if (! in_array('C', $issueColumns, true)) {
                                $issueColumns[] = 'C';
                            }
                        }

                        if (! $clockOut) {
                            $hasIssue = true;
                            if (! in_array('D', $issueColumns, true)) {
                                $issueColumns[] = 'D';
                            }
                        }

                        if ($clockIn && (! $earliestIn || $clockIn->lt($earliestIn))) {
                            $earliestIn = $clockIn->copy();
                            $baselineClockForSchedule = $clock;
                            $firstLocIn = $clock->location_type === 'float'
                                ? ($clock->address_clock_in ?? '')
                                : ($clock->location_type === 'home'
                                    ? 'home'
                                    : ($clock->location_type === 'site' && $clock->clock_in ? optional($clock->location)->name : ''));
                        }

                        if ($clockOut && (! $latestOut || $clockOut->gt($latestOut))) {
                            $latestOut = $clockOut->copy();
                            $lastLocOut = $clock->location_type === 'float'
                                ? ($clock->address_clock_out ?? '')
                                : ($clock->location_type === 'home'
                                    ? 'home'
                                    : ($clock->location_type === 'site' && $clock->clock_out ? optional($clock->location)->name : ''));
                        }

                        $segments[] = [
                            'in' => $clockIn ? $clockIn->copy()->setTimezone($userTimezoneName)->format('h:i A') : '',
                            'out' => $clockOut ? $clockOut->copy()->setTimezone($userTimezoneName)->format('h:i A') : '',
                        ];
                    }
                }

                $earliestInLocal = $earliestIn ? $earliestIn->copy()->setTimezone($userTimezoneName) : null;
                $latestOutLocal = $latestOut ? $latestOut->copy()->setTimezone($userTimezoneName) : null;

                $effectiveSchedule = $this->resolveEffectiveSchedule(
                    $user,
                    $baselineClockForSchedule,
                    $localDate,
                    $userTimezoneName,
                    $scheduledStartTime,
                    $requiredMinutesPerDay,
                    $isFlexibleSchedule
                );

                $scheduledStartLocal = $effectiveSchedule['start'];
                $scheduledEndLocal = $effectiveSchedule['end'];
                $expectedScheduledMinutes = $effectiveSchedule['minutes'];
                $scheduleSource = $effectiveSchedule['source'] ?? 'default';

                $isFriday = $localDate->isFriday();
                $isSaturday = $localDate->isSaturday();
                $isNonWorkingWeekend = $isFriday || ($isSaturday && ! $worksOnSaturday);

                $requiredMinutes = $isNonWorkingWeekend ? 0 : $expectedScheduledMinutes;
                $totalRequiredMinutes += $requiredMinutes;

                if ($hasIssue) {
                    $totalIssueDays++;
                    $requiredMinutes = 0;
                }

                $latenessBeyondGrace = 0;
                $latenessMinutesActual = 0;
                if ($earliestIn && $requiredMinutes > 0 && $scheduledStartLocal) {
                    $baselineStartUtc = $scheduledStartLocal->copy()->setTimezone('UTC');
                    $graceEnd = $baselineStartUtc->copy()->addMinutes($planGraceMinutes);

                    $diffActual = $baselineStartUtc->diffInMinutes($earliestIn, false);
                    if ($diffActual > 0) {
                        $latenessMinutesActual = $diffActual;
                    }

                    if ($earliestIn->greaterThan($graceEnd)) {
                        $latenessBeyondGrace = $graceEnd->diffInMinutes($earliestIn);
                    }
                }

                $scheduledStartMinutes = $scheduledStartLocal ? ($scheduledStartLocal->hour * 60 + $scheduledStartLocal->minute) : null;
                $scheduledEndMinutes = $scheduledEndLocal ? ($scheduledEndLocal->hour * 60 + $scheduledEndLocal->minute) : null;
                $earliestInMinutes = $earliestInLocal ? ($earliestInLocal->hour * 60 + $earliestInLocal->minute) : null;
                $latestOutMinutes = $latestOutLocal ? ($latestOutLocal->hour * 60 + $latestOutLocal->minute) : null;
                $workedMeetsExpected = $expectedScheduledMinutes > 0 && $workedMinutes >= $expectedScheduledMinutes;
                $workedMeetsRequired = $requiredMinutes > 0 && $workedMinutes >= $requiredMinutes;

                $dailyExcuseMinutes = $user->excuses()->where('status', 'approved')
                    ->whereDate('date', $formattedDate)
                    ->get()
                    ->sum(fn ($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
                $totalApprovedExcuseMinutes += $dailyExcuseMinutes;

                $attendanceOvertimeMinutes = $this->computeAttendanceOvertimeMinutes($workedMinutes, $formattedDate);

                $overtimeRecord = $overtimeRecords->get($formattedDate);
                $recordedOtMinutes = $overtimeRecord ? (int) $overtimeRecord->overtime_minutes : null;
                if ($recordedOtMinutes === null) {
                    $recordedOtMinutes = $attendanceOvertimeMinutes;
                }

                if ($isPartTime) {
                    $attendanceOvertimeMinutes = 0;
                    $recordedOtMinutes = 0;
                    $latenessMinutesActual = 0;
                    $latenessBeyondGrace = 0;
                }

                $shortfall = max(0, $requiredMinutes - $workedMinutes);

                $dailyLocationTypes = $dailyClocks
                    ->pluck('location_type')
                    ->filter()
                    ->map(function ($type) {
                        return is_string($type) ? strtolower($type) : $type;
                    })
                    ->unique()
                    ->values()
                    ->all();

                $evaluationMetrics = [
                    'date' => $formattedDate,
                    'day_of_week' => strtolower($localDate->format('l')),
                    'required_minutes' => $requiredMinutes,
                    'default_daily_minutes' => $requiredMinutesPerDay,
                    'expected_minutes' => $expectedScheduledMinutes,
                    'worked_minutes' => $workedMinutes,
                    'shortfall_minutes' => $shortfall,
                    'lateness_minutes_actual' => $latenessMinutesActual ?? 0,
                    'lateness_beyond_grace' => $latenessBeyondGrace,
                    'attendance_overtime_minutes' => $attendanceOvertimeMinutes,
                    'recorded_ot_minutes' => $recordedOtMinutes,
                    'is_issue' => $hasIssue,
                    'is_vacation' => $isVacation,
                    'location_types' => $dailyLocationTypes,
                    'user_work_types' => $userWorkTypes,
                    'expected_schedule_start' => $scheduledStartLocal ? $scheduledStartLocal->format('H:i:s') : null,
                    'expected_schedule_end' => $scheduledEndLocal ? $scheduledEndLocal->format('H:i:s') : null,
                    'expected_schedule_start_minutes' => $scheduledStartMinutes,
                    'expected_schedule_end_minutes' => $scheduledEndMinutes,
                    'expected_schedule_source' => $scheduleSource,
                    'first_clock_in_time' => $earliestInLocal ? $earliestInLocal->format('H:i:s') : null,
                    'first_clock_in_minutes' => $earliestInMinutes,
                    'last_clock_out_time' => $latestOutLocal ? $latestOutLocal->format('H:i:s') : null,
                    'last_clock_out_minutes' => $latestOutMinutes,
                    'worked_minutes_meets_expected' => $workedMeetsExpected,
                    'worked_minutes_meets_required' => $workedMeetsRequired,
                    'is_part_time' => $isPartTime,
                    'overtime_locked' => $isPartTime,
                ];

                $appliedRules = [];
                $planMonetaryDeduction = 0.0;
                $rawDeductionMinutes = 0;
                $skipDeductions = $isPartTime || $isVacation || $isNonWorkingWeekend;

                if ($skipDeductions) {
                    $latenessMinutesActual = 0;
                    $latenessBeyondGrace = 0;
                } else {
                    $evaluation = $deductionEngine->evaluate($evaluationMetrics, $ruleState);
                    $appliedRules = $evaluation['applied_rules'] ?? [];
                    $planMonetaryDeduction = $evaluation['monetary_amount'] ?? 0.0;
                    $totalPlanMonetaryAmount += $planMonetaryDeduction;
                    $rawDeductionMinutes = $hasIssue ? 0 : (int) ($evaluation['deduction_minutes'] ?? 0);

                    if ($hasIssue || $rawDeductionMinutes === 0) {
                        $latenessBeyondGrace = 0;
                    }
                }

                $deductionDetailSummary = collect($appliedRules)
                    ->map(function (array $rule) {
                        $minutes = $rule['deduction_minutes'] ?? 0;
                        $label = $rule['label'] ?? 'Rule';

                        return $label . ' (' . $this->formatMinutes($minutes) . ')';
                    })
                    ->implode('; ');

                if (! empty($appliedRules)) {
                    foreach ($appliedRules as $rule) {
                        $categoryKey = $rule['category'] ?? 'other';
                        $color = $rule['color'] ?? null;

                        if ($color) {
                            $this->ruleLegend[$categoryKey] = strtoupper(ltrim($color, '#'));
                        } elseif (! isset($this->ruleLegend[$categoryKey])) {
                            $this->ruleLegend[$categoryKey] = $this->defaultCategoryColors[$categoryKey] ?? $this->defaultCategoryColors['other'];
                        }

                        $this->planRows->push([
                            'Employee' => $user->name,
                            'Code' => $user->code,
                            'Department' => $user->department?->name ?? 'N/A',
                            'Date' => $formattedDate,
                            'Category' => ucfirst(str_replace('_', ' ', $rule['category'] ?? 'other')),
                            'Rule' => $rule['label'] ?? '',
                            'Deduction Minutes' => $rule['deduction_minutes'] ?? 0,
                            'Deduction HH:MM' => $this->formatMinutes($rule['deduction_minutes'] ?? 0),
                            'Monetary Amount' => $rule['monetary_amount'] ?? null,
                            'Notes' => $rule['notes'] ?? null,
                            'Source' => is_array($rule['source'] ?? null) ? ($rule['source']['type'] ?? null) : null,
                            'Color' => $rule['color'] ?? ($this->defaultCategoryColors[$rule['category'] ?? 'other'] ?? $this->defaultCategoryColors['other']),
                        ]);
                    }
                } else {
                    $deductionDetailSummary = '';
                }

                if ($workedMinutes > 0) {
                    $totalWorkedDays++;
                }

                $totalWorkedMinutes += $workedMinutes;
                $totalRecordedOtMinutes += $recordedOtMinutes;
                $totalAttendanceOtMinutes += $attendanceOvertimeMinutes;
                $totalRawDeductionMinutes += $rawDeductionMinutes;
                $totalLatenessBeyondGrace += $latenessBeyondGrace;

                $dailyEntries[] = [
                    'row_data' => [
                        'Date' => $formattedDate,
                        'Name' => $user->name,
                        'Clock In' => $dailyClocks->count() > 1 ? '' : ($earliestIn ? $earliestIn->copy()->setTimezone($userTimezoneName)->format('h:i A') : ''),
                        'Clock Out' => $dailyClocks->count() > 1 ? '' : ($latestOut ? $latestOut->copy()->setTimezone($userTimezoneName)->format('h:i A') : ''),
                        'Code' => $user->code,
                        'Department' => $user->department?->name ?? 'N/A',
                        'Total Hours in That Day' => $this->formatMinutes($workedMinutes),
                        'Total Over time in That Day' => $this->formatMinutes($recordedOtMinutes),
                        'Plan Deduction in That Day' => $this->formatMinutes($rawDeductionMinutes),
                        'Deduction Details' => $deductionDetailSummary,
                        'Excuse Deducted in That Day' => '',
                        'Excuse Remaining (Policy 4h)' => '',
                        'Total Excuses in That Day' => $this->formatMinutes($dailyExcuseMinutes),
                        'Is this date has vacation' => $vacationLabel,
                        'Location In' => $firstLocIn,
                        'Location Out' => $lastLocOut,
                        'Attendance Over time in That Day' => $this->formatMinutes($attendanceOvertimeMinutes),
                    ],
                    'segments' => $dailyClocks->count() > 1 ? $segments : [],
                    'weekend' => Carbon::parse($formattedDate)->isFriday() || Carbon::parse($formattedDate)->isSaturday(),
                    'ot_status' => $recordedOtMinutes > 0
                        ? strtolower($overtimeRecord?->overall_status ?? 'pending')
                        : ($attendanceOvertimeMinutes > 0 ? 'pending' : null),
                    'raw_deduction_minutes' => $rawDeductionMinutes,
                    'excuse_applied_minutes' => 0,
                    'chargeable_deduction_minutes' => $rawDeductionMinutes,
                    'deduction_rules' => $appliedRules,
                    'deduction_detail' => $deductionDetailSummary,
                    'plan_monetary_amount' => $planMonetaryDeduction,
                    'deduction_color' => $appliedRules[0]['color'] ?? null,
                    'is_vacation' => $isVacation,
                    'issue_columns' => $issueColumns,
                ];
            }

            array_unshift($dailyEntries, [
                'row_data' => [
                    'Date' => 'Employee: ' . $user->name,
                    'Name' => 'Code: ' . ($user->code ?? 'N/A'),
                    'Clock In' => '',
                    'Clock Out' => '',
                    'Code' => '',
                    'Department' => 'Department: ' . ($user->department?->name ?? 'N/A'),
                    'Total Hours in That Day' => '',
                    'Total Over time in That Day' => '',
                    'Plan Deduction in That Day' => '',
                    'Deduction Details' => '',
                    'Excuse Deducted in That Day' => '',
                    'Excuse Remaining (Policy 4h)' => '',
                    'Total Excuses in That Day' => '',
                    'Is this date has vacation' => 'Vacation Days Left: ' . number_format($vacationDaysLeft, 2),
                    'Location In' => '',
                    'Location Out' => '',
                    'Attendance Over time in That Day' => '',
                ],
                'segments' => [],
                'weekend' => false,
                'ot_status' => null,
                'raw_deduction_minutes' => 0,
                'excuse_applied_minutes' => 0,
                'chargeable_deduction_minutes' => 0,
                'deduction_rules' => [],
                'deduction_detail' => '',
                'plan_monetary_amount' => null,
                'deduction_color' => null,
                'is_vacation' => false,
                'issue_columns' => [],
                'header_row' => true,
            ]);

            $excuseMinutesRemaining = 240;
            $dailyIndicesByDeduction = collect($dailyEntries)
                ->filter(fn ($entry) => $entry['raw_deduction_minutes'] >= 120)
                ->sortByDesc('raw_deduction_minutes')
                ->keys();

            $excuseTokensUsed = 0;
            foreach ($dailyIndicesByDeduction as $index) {
                if ($excuseMinutesRemaining < 120) {
                    break;
                }

                $dailyEntries[$index]['excuse_applied_minutes'] = 120;
                $dailyEntries[$index]['chargeable_deduction_minutes'] = max(0, $dailyEntries[$index]['raw_deduction_minutes'] - 120);
                $excuseMinutesRemaining -= 120;
                $excuseTokensUsed++;
            }

            foreach ($dailyEntries as $entry) {
                if (! empty($entry['header_row'])) {
                    $row = $entry['row_data'];
                    $aggregatedRow = $row;
                    $aggregatedRow['Plan Monetary Amount'] = null;
                    $this->aggregatedRows->push($aggregatedRow);

                    $this->detailedRows->push($row);
                    $rowNumber = 1 + $this->detailedRows->count();
                    $this->rowStyles[] = [
                        'row' => $rowNumber,
                        'header_row' => true,
                    ];

                    continue;
                }

                $totalExcuseMinutesApplied += $entry['excuse_applied_minutes'];
                $totalChargeableDeductionMinutes += $entry['chargeable_deduction_minutes'];

                $row = $entry['row_data'];
                $row['Excuse Deducted in That Day'] = $this->formatMinutes($entry['excuse_applied_minutes']);
                $row['Excuse Remaining (Policy 4h)'] = '';

                $aggregatedRow = $row;
                $aggregatedRow['Plan Monetary Amount'] = $entry['plan_monetary_amount'] !== null
                    ? round($entry['plan_monetary_amount'], 2)
                    : null;
                $this->aggregatedRows->push($aggregatedRow);

                $this->detailedRows->push($row);
                $rowNumber = 1 + $this->detailedRows->count();
                $this->rowStyles[] = [
                    'row' => $rowNumber,
                    'ot_status' => $entry['ot_status'],
                    'weekend' => $entry['weekend'],
                    'issue_columns' => $entry['issue_columns'] ?? [],
                    'deduction_color' => $entry['deduction_color'] ?? null,
                    'deduction_rules' => $entry['deduction_rules'] ?? [],
                    'vacation' => $entry['is_vacation'] ?? false,
                ];

                foreach ($entry['segments'] as $segment) {
                    $this->detailedRows->push([
                        'Date' => '',
                        'Name' => '',
                        'Clock In' => $segment['in'],
                        'Clock Out' => $segment['out'],
                        'Code' => '',
                        'Department' => '',
                        'Total Hours in That Day' => '',
                        'Total Over time in That Day' => '',
                        'Plan Deduction in That Day' => '',
                        'Deduction Details' => '',
                        'Excuse Deducted in That Day' => '',
                        'Excuse Remaining (Policy 4h)' => '',
                        'Total Excuses in That Day' => '',
                        'Is this date has vacation' => '',
                        'Location In' => '',
                        'Location Out' => '',
                        'Attendance Over time in That Day' => '',
                    ]);

                    $this->aggregatedRows->push([
                        'Date' => '',
                        'Name' => '',
                        'Clock In' => $segment['in'],
                        'Clock Out' => $segment['out'],
                        'Code' => '',
                        'Department' => '',
                        'Total Hours in That Day' => '',
                        'Total Over time in That Day' => '',
                        'Plan Deduction in That Day' => '',
                        'Deduction Details' => '',
                        'Excuse Deducted in That Day' => '',
                        'Excuse Remaining (Policy 4h)' => '',
                        'Total Excuses in That Day' => '',
                        'Is this date has vacation' => '',
                        'Location In' => '',
                        'Location Out' => '',
                        'Attendance Over time in That Day' => '',
                        'Plan Monetary Amount' => null,
                    ]);

                    $rowNumber = 1 + $this->detailedRows->count();
                    $this->rowStyles[] = [
                        'row' => $rowNumber,
                        'multi_segment' => true,
                        'issue_columns' => [],
                    ];
                }
            }

            $this->detailedRows->push([
                'Date' => '---TOTAL for ' . $user->name . '----',
                'Name' => $user->name,
                'Clock In' => 'N/A',
                'Clock Out' => 'N/A',
                'Code' => $user->code,
                'Department' => $user->department?->name ?? 'N/A',
                'Total Hours in That Day' => $this->formatMinutes($totalWorkedMinutes),
                'Total Over time in That Day' => $this->formatMinutes($totalRecordedOtMinutes),
                'Plan Deduction in That Day' => $this->formatMinutes($totalRawDeductionMinutes),
                'Deduction Details' => '',
                'Excuse Deducted in That Day' => $this->formatMinutes($totalExcuseMinutesApplied),
                'Excuse Remaining (Policy 4h)' => $this->formatMinutes(max(0, $excuseMinutesRemaining)),
                'Total Excuses in That Day' => $this->formatMinutes($totalApprovedExcuseMinutes),
                'Is this date has vacation' => $totalVacationDays === 0 ? 'NO VACATIONS' : $totalVacationDays . ' Days',
                'Location In' => 'N/A',
                'Location Out' => 'N/A',
                'Attendance Over time in That Day' => $this->formatMinutes($totalAttendanceOtMinutes),
            ]);

            $this->aggregatedRows->push([
                'Date' => '---TOTAL for ' . $user->name . '----',
                'Name' => $user->name,
                'Clock In' => 'N/A',
                'Clock Out' => 'N/A',
                'Code' => $user->code,
                'Department' => $user->department?->name ?? 'N/A',
                'Total Hours in That Day' => $this->formatMinutes($totalWorkedMinutes),
                'Total Over time in That Day' => $this->formatMinutes($totalRecordedOtMinutes),
                'Plan Deduction in That Day' => $this->formatMinutes($totalRawDeductionMinutes),
                'Deduction Details' => '',
                'Excuse Deducted in That Day' => $this->formatMinutes($totalExcuseMinutesApplied),
                'Excuse Remaining (Policy 4h)' => $this->formatMinutes(max(0, $excuseMinutesRemaining)),
                'Total Excuses in That Day' => $this->formatMinutes($totalApprovedExcuseMinutes),
                'Is this date has vacation' => $totalVacationDays === 0 ? 'NO VACATIONS' : $totalVacationDays . ' Days',
                'Location In' => 'N/A',
                'Location Out' => 'N/A',
                'Attendance Over time in That Day' => $this->formatMinutes($totalAttendanceOtMinutes),
                'Plan Monetary Amount' => round($totalPlanMonetaryAmount, 2),
            ]);

            $this->detailedRows->push([
                'Date' => '--------------------',
                'Name' => '',
                'Clock In' => '',
                'Clock Out' => '',
                'Code' => '',
                'Department' => '',
                'Total Hours in That Day' => '',
                'Total Over time in That Day' => '',
                'Plan Deduction in That Day' => '',
                'Deduction Details' => '',
                'Excuse Deducted in That Day' => '',
                'Excuse Remaining (Policy 4h)' => '',
                'Total Excuses in That Day' => '',
                'Is this date has vacation' => '',
                'Location In' => '',
                'Location Out' => '',
                'Attendance Over time in That Day' => '',
            ]);

            $this->aggregatedRows->push([
                'Date' => '--------------------',
                'Name' => '',
                'Clock In' => '',
                'Clock Out' => '',
                'Code' => '',
                'Department' => '',
                'Total Hours in That Day' => '',
                'Total Over time in That Day' => '',
                'Plan Deduction in That Day' => '',
                'Deduction Details' => '',
                'Excuse Deducted in That Day' => '',
                'Excuse Remaining (Policy 4h)' => '',
                'Total Excuses in That Day' => '',
                'Is this date has vacation' => '',
                'Location In' => '',
                'Location Out' => '',
                'Attendance Over time in That Day' => '',
                'Plan Monetary Amount' => null,
            ]);

            if ($this->planRows->count() === $userPlanRowStart) {
                $this->planRows->push([
                    'Employee' => $user->name,
                    'Code' => $user->code,
                    'Department' => $user->department?->name ?? 'N/A',
                    'Date' => '',
                    'Category' => 'No Conditions',
                    'Rule' => 'No deduction rules triggered',
                    'Deduction Minutes' => 0,
                    'Deduction HH:MM' => '00:00',
                    'Monetary Amount' => null,
                    'Notes' => '',
                    'Source' => '',
                    'Color' => $this->defaultCategoryColors['other'],
                ]);
            }

            $excuseStats = $this->collectExcuseStats($user, $startDate->copy(), $endDate->copy(), $userTimezoneName);
            $excuseSummaryForNotes = $this->summarizeExcuseStatsForNotes($excuseStats);

            $hourlyRate = $userDetail->hourly_rate;
            $overtimeRate = $userDetail->overtime_hourly_rate;
            $baseSalary = $userDetail->salary;

            $workedHours = $totalWorkedMinutes / 60;
            $recordedOtHours = $totalRecordedOtMinutes / 60;

            $workedPay = $hourlyRate !== null ? round($hourlyRate * $workedHours, 2) : null;
            $otPay = $overtimeRate !== null ? round($overtimeRate * $recordedOtHours, 2) : null;
            $grossPay = ($baseSalary ?? 0) + ($workedPay ?? 0) + ($otPay ?? 0);

            $deductionAmount = null;
            if ($hourlyRate !== null) {
                $deductionAmount = round(($hourlyRate / 60) * $totalChargeableDeductionMinutes, 2);
            }

            $netPay = $grossPay - ($deductionAmount ?? 0);

            $notes = sprintf(
                'Required %s, worked %s, shortfall beyond grace %s. Late beyond grace not recouped: %s. Excuse tokens used: %d. Chargeable deduction %s. Plan deduction amount %.2f. Excuses summary: %s.',
                $this->formatMinutes($totalRequiredMinutes),
                $this->formatMinutes($totalWorkedMinutes),
                $this->formatMinutes($totalRawDeductionMinutes),
                $this->formatMinutes($totalLatenessBeyondGrace),
                $excuseTokensUsed,
                $this->formatMinutes($totalChargeableDeductionMinutes),
                round($totalPlanMonetaryAmount, 2),
                $excuseSummaryForNotes
            );

            $summaryRow = [
                'Employee' => $user->name,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? 'N/A',
                'Total Days Worked' => $totalWorkedDays,
                'Total Worked Hours' => $this->formatMinutes($totalWorkedMinutes),
                'Total OT Hours' => $this->formatMinutes($totalRecordedOtMinutes),
                'Total Attendance OT Hours' => $this->formatMinutes($totalAttendanceOtMinutes),
                'Raw Deduction Hours' => $this->formatMinutes($totalRawDeductionMinutes),
                'Excuse Hours Used' => $this->formatMinutes($totalExcuseMinutesApplied),
                'Approved Excuses' => $this->formatExcuseStat($excuseStats['approved'] ?? []),
                'Pending Excuses' => $this->formatExcuseStat($excuseStats['pending'] ?? []),
                'Rejected Excuses' => $this->formatExcuseStat($excuseStats['rejected'] ?? []),
                'Chargeable Deduction Hours' => $this->formatMinutes($totalChargeableDeductionMinutes),
                'Issue Days' => $totalIssueDays,
                'Vacation Days' => $totalVacationDays,
                'Vacation Days Left' => round($vacationDaysLeft, 2),
                'Base Salary' => $baseSalary !== null ? round($baseSalary, 2) : null,
                'Hourly Rate' => $hourlyRate !== null ? round($hourlyRate, 2) : null,
                'Worked Pay' => $workedPay !== null ? round($workedPay, 2) : null,
                'OT Rate' => $overtimeRate !== null ? round($overtimeRate, 2) : null,
                'OT Pay' => $otPay !== null ? round($otPay, 2) : null,
                'Gross Pay' => $grossPay !== 0.0 ? round($grossPay, 2) : null,
                'Deduction Amount' => $deductionAmount !== null ? round($deductionAmount, 2) : null,
                'Plan Deduction Amount' => $totalPlanMonetaryAmount !== 0.0 ? round($totalPlanMonetaryAmount, 2) : null,
                'Net Pay' => $netPay !== 0.0 ? round($netPay, 2) : null,
                'Notes' => $notes,
            ];

            $this->summaryRows->push($summaryRow);
            $this->summaryRowComparisons[] = [
                'employee' => $user->name,
                'worked_minutes' => $totalWorkedMinutes,
                'required_minutes' => $totalRequiredMinutes,
            ];

            $overallTotals['required_minutes'] += $totalRequiredMinutes;
            $overallTotals['worked_minutes'] += $totalWorkedMinutes;
            $overallTotals['recorded_ot_minutes'] += $totalRecordedOtMinutes;
            $overallTotals['attendance_ot_minutes'] += $totalAttendanceOtMinutes;
            $overallTotals['raw_deduction_minutes'] += $totalRawDeductionMinutes;
            $overallTotals['excuse_minutes'] += $totalExcuseMinutesApplied;
            $overallTotals['chargeable_deduction_minutes'] += $totalChargeableDeductionMinutes;
            $overallTotals['vacation_days'] += $totalVacationDays;
            $overallTotals['base_salary'] += $baseSalary !== null ? $baseSalary : 0.0;
            $overallTotals['worked_pay'] += $workedPay !== null ? $workedPay : 0.0;
            $overallTotals['ot_pay'] += $otPay !== null ? $otPay : 0.0;
            $overallTotals['gross_pay'] += $grossPay;
            $overallTotals['deduction_amount'] += $deductionAmount !== null ? $deductionAmount : 0.0;
            $overallTotals['plan_monetary_amount'] += $totalPlanMonetaryAmount;
            $overallTotals['net_pay'] += $netPay;
            $overallTotals['excuse_tokens_used'] += $excuseTokensUsed;
            $overallTotals['lateness_beyond_grace_minutes'] += $totalLatenessBeyondGrace;
            $overallTotals['issue_days'] = ($overallTotals['issue_days'] ?? 0) + $totalIssueDays;
            $overallTotals['worked_days'] += $totalWorkedDays;
            foreach ($this->excuseStatusOrder as $statusKey) {
                $statusStats = $excuseStats[$statusKey] ?? ['count' => 0, 'minutes' => 0];
                $overallTotals[$statusKey . '_excuse_count'] += $statusStats['count'] ?? 0;
                $overallTotals[$statusKey . '_excuse_minutes'] += $statusStats['minutes'] ?? 0;
            }
        }

        if ($this->summaryRows->isNotEmpty()) {
            $overallExcuseStats = $this->buildOverallExcuseStats($overallTotals);
            $overallExcuseSummary = $this->summarizeExcuseStatsForNotes($overallExcuseStats);
            $notes = sprintf(
                'Required %s, worked %s, shortfall beyond grace %s. Late beyond grace not recouped: %s. Excuse tokens used across all employees: %d. Chargeable deduction %s. Plan deduction amount %.2f. Issue days: %d. Excuses summary: %s.',
                $this->formatMinutes($overallTotals['required_minutes']),
                $this->formatMinutes($overallTotals['worked_minutes']),
                $this->formatMinutes($overallTotals['raw_deduction_minutes']),
                $this->formatMinutes($overallTotals['lateness_beyond_grace_minutes']),
                $overallTotals['excuse_tokens_used'],
                $this->formatMinutes($overallTotals['chargeable_deduction_minutes']),
                round($overallTotals['plan_monetary_amount'], 2),
                $overallTotals['issue_days'] ?? 0,
                $overallExcuseSummary
            );

            $summaryRow = [
                'Employee' => 'TOTAL',
                'Code' => '',
                'Department' => '',
                'Total Days Worked' => $overallTotals['worked_days'],
                'Total Worked Hours' => $this->formatMinutes($overallTotals['worked_minutes']),
                'Total OT Hours' => $this->formatMinutes($overallTotals['recorded_ot_minutes']),
                'Total Attendance OT Hours' => $this->formatMinutes($overallTotals['attendance_ot_minutes']),
                'Raw Deduction Hours' => $this->formatMinutes($overallTotals['raw_deduction_minutes']),
                'Excuse Hours Used' => $this->formatMinutes($overallTotals['excuse_minutes']),
                'Approved Excuses' => $this->formatExcuseStat($overallExcuseStats['approved']),
                'Pending Excuses' => $this->formatExcuseStat($overallExcuseStats['pending']),
                'Rejected Excuses' => $this->formatExcuseStat($overallExcuseStats['rejected']),
                'Chargeable Deduction Hours' => $this->formatMinutes($overallTotals['chargeable_deduction_minutes']),
                'Issue Days' => $overallTotals['issue_days'] ?? 0,
                'Vacation Days' => $overallTotals['vacation_days'],
                'Vacation Days Left' => round($overallTotals['vacation_days_left'], 2),
                'Base Salary' => $overallTotals['base_salary'] !== 0.0 ? round($overallTotals['base_salary'], 2) : null,
                'Hourly Rate' => null,
                'Worked Pay' => $overallTotals['worked_pay'] !== 0.0 ? round($overallTotals['worked_pay'], 2) : null,
                'OT Rate' => null,
                'OT Pay' => $overallTotals['ot_pay'] !== 0.0 ? round($overallTotals['ot_pay'], 2) : null,
                'Gross Pay' => $overallTotals['gross_pay'] !== 0.0 ? round($overallTotals['gross_pay'], 2) : null,
                'Deduction Amount' => $overallTotals['deduction_amount'] !== 0.0 ? round($overallTotals['deduction_amount'], 2) : null,
                'Plan Deduction Amount' => $overallTotals['plan_monetary_amount'] !== 0.0 ? round($overallTotals['plan_monetary_amount'], 2) : null,
                'Net Pay' => $overallTotals['net_pay'] !== 0.0 ? round($overallTotals['net_pay'], 2) : null,
                'Notes' => $notes,
            ];

            $this->summaryRows->push($summaryRow);
            $this->summaryRowComparisons[] = [
                'employee' => 'TOTAL',
                'worked_minutes' => $overallTotals['worked_minutes'],
                'required_minutes' => $overallTotals['required_minutes'],
            ];
        }
    }

    protected function normalizeUsers($users): Collection
    {
        if ($users instanceof User) {
            return collect([$users]);
        }

        if ($users instanceof Collection || $users instanceof \Illuminate\Database\Eloquent\Collection) {
            return collect($users);
        }

        return collect(is_array($users) ? $users : [$users]);
    }

    protected function buildOverallExcuseStats(array $overallTotals): array
    {
        $stats = $this->initializeExcuseStats();

        foreach ($this->excuseStatusOrder as $status) {
            $stats[$status]['count'] = $overallTotals[$status . '_excuse_count'] ?? 0;
            $stats[$status]['minutes'] = $overallTotals[$status . '_excuse_minutes'] ?? 0;
        }

        return $stats;
    }

    protected function collectExcuseStats(User $user, Carbon $startDate, Carbon $endDate, string $timezone): array
    {
        $stats = $this->initializeExcuseStats();

        $localStart = $startDate->copy()->setTimezone($timezone)->startOfDay();
        $localEnd = $endDate->copy()->setTimezone($timezone)->endOfDay();

        $excuses = $user->excuses()
            ->whereBetween('date', [$localStart->toDateString(), $localEnd->toDateString()])
            ->get();

        foreach ($excuses as $excuse) {
            $status = $excuse->status ?? 'pending';

            if ($status instanceof \UnitEnum) {
                $status = method_exists($status, 'value') ? $status->value : $status->name;
            }

            $statusKey = is_string($status) ? strtolower($status) : 'pending';
            if (! in_array($statusKey, $this->excuseStatusOrder, true)) {
                $statusKey = 'pending';
            }
            if (! isset($stats[$statusKey])) {
                $stats[$statusKey] = ['count' => 0, 'minutes' => 0];
            }

            $from = $excuse->from ? Carbon::parse($excuse->from) : null;
            $to = $excuse->to ? Carbon::parse($excuse->to) : null;
            $minutes = 0;

            if ($from && $to) {
                $diff = $from->diffInMinutes($to, false);
                $minutes = $diff > 0 ? $diff : 0;
            }

            $stats[$statusKey]['count']++;
            $stats[$statusKey]['minutes'] += $minutes;
        }

        return $stats;
    }

    protected function formatExcuseStat(array $stat): ?string
    {
        $count = (int) ($stat['count'] ?? 0);
        $minutes = (int) ($stat['minutes'] ?? 0);

        if ($count === 0 && $minutes === 0) {
            return null;
        }

        return sprintf('%d (%s)', $count, $this->formatMinutes($minutes));
    }

    protected function summarizeExcuseStatsForNotes(array $stats): string
    {
        $parts = [];

        foreach ($this->excuseStatusOrder as $status) {
            $count = (int) ($stats[$status]['count'] ?? 0);
            $minutes = (int) ($stats[$status]['minutes'] ?? 0);

            if ($count === 0 && $minutes === 0) {
                continue;
            }

            $parts[] = sprintf('%s %d (%s)', ucfirst($status), $count, $this->formatMinutes($minutes));
        }

        return $parts ? implode(', ', $parts) : 'No excuses recorded';
    }

    protected function initializeExcuseStats(): array
    {
        $stats = [];

        foreach ($this->excuseStatusOrder as $status) {
            $stats[$status] = ['count' => 0, 'minutes' => 0];
        }

        return $stats;
    }

    protected function resolveEffectiveSchedule(
        User $user,
        ?ClockInOut $baselineClock,
        Carbon $localDate,
        string $userTimezoneName,
        ?string $scheduledStartTime,
        int $defaultRequiredMinutes,
        bool $isFlexibleSchedule
    ): array {
        $makeDateTime = static function (Carbon $date, ?string $time, string $tz): ?Carbon {
            if ($time === null) {
                return null;
            }

            $time = trim((string) $time);
            if ($time === '') {
                return null;
            }

            $dateString = $date->toDateString() . ' ' . $time;

            try {
                return Carbon::parse($dateString, $tz);
            } catch (\Throwable $throwable) {
                return null;
            }
        };

        $dateForCalculation = $localDate->copy();
        $defaultStartString = $scheduledStartTime ?: '08:00:00';
        $defaultStart = $makeDateTime($dateForCalculation, $defaultStartString, $userTimezoneName)
            ?? Carbon::parse($dateForCalculation->toDateString() . ' 08:00:00', $userTimezoneName);

        $userDetail = optional($user->user_detail);
        $defaultEndString = $userDetail->end_time ?? null;
        $defaultEnd = $defaultEndString
            ? $makeDateTime($dateForCalculation, $defaultEndString, $userTimezoneName)
            : null;

        if (! $defaultEnd) {
            $minutes = $defaultRequiredMinutes > 0 ? $defaultRequiredMinutes : 480;
            $defaultEnd = $defaultStart->copy()->addMinutes($minutes);
        }

        $defaultMinutes = $defaultEnd->diffInMinutes($defaultStart);
        if ($defaultMinutes <= 0) {
            $defaultMinutes = $defaultRequiredMinutes > 0 ? $defaultRequiredMinutes : 480;
            $defaultEnd = $defaultStart->copy()->addMinutes($defaultMinutes);
        }

        $start = $defaultStart->copy();
        $end = $defaultEnd->copy();
        $source = 'default';
        $startAdjusted = false;
        $endAdjusted = false;

        if ($baselineClock instanceof ClockInOut) {
            $baselineClock->loadMissing('location');

            $locationType = strtolower((string) $baselineClock->location_type);
            if ($locationType === 'site') {
                $location = $baselineClock->location;
                $locationStart = $location ? $makeDateTime($dateForCalculation, $location->start_time ?? null, $userTimezoneName) : null;
                $locationEnd = $location ? $makeDateTime($dateForCalculation, $location->end_time ?? null, $userTimezoneName) : null;

                if ($locationStart) {
                    $start = $locationStart;
                    $startAdjusted = true;
                }

                if ($locationEnd) {
                    $end = $locationEnd;
                    $endAdjusted = true;
                }

                if ($locationStart || $locationEnd) {
                    $source = 'location_site';
                }
            } elseif ($locationType === 'home') {
                $homeStart = $makeDateTime($dateForCalculation, $userDetail->start_time ?? null, $userTimezoneName);
                $homeEnd = $makeDateTime($dateForCalculation, $userDetail->end_time ?? null, $userTimezoneName);

                if ($homeStart) {
                    $start = $homeStart;
                    $startAdjusted = true;
                }

                if ($homeEnd) {
                    $end = $homeEnd;
                    $endAdjusted = true;
                }

                if ($homeStart || $homeEnd) {
                    $source = 'home_detail';
                }
            }
        }

        if ($source === 'default' && $isFlexibleSchedule) {
            $flexibleStartTime = optional($user->subDepartment)->flexible_start_time
                ?? optional($user->department)->flexible_start_time
                ?? null;

            $flexibleStart = $makeDateTime($dateForCalculation, $flexibleStartTime, $userTimezoneName);
            if ($flexibleStart) {
                $start = $flexibleStart;
                $source = 'flexible';
                $startAdjusted = true;
            }
        }

        if ($startAdjusted && ! $endAdjusted) {
            $end = $start->copy()->addMinutes($defaultMinutes);
            $endAdjusted = true;
        }

        if (! $end) {
            $end = $start->copy()->addMinutes($defaultMinutes);
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addMinutes($defaultMinutes);
        }

        $minutes = $end->diffInMinutes($start);
        if ($minutes <= 0) {
            $minutes = $defaultMinutes > 0 ? $defaultMinutes : $defaultRequiredMinutes;
            $minutes = max(0, (int) $minutes);
            $end = $start->copy()->addMinutes($minutes);
        }

        return [
            'start' => $start,
            'end' => $end,
            'minutes' => (int) max(0, $minutes),
            'source' => $source,
        ];
    }

    protected function computeAttendanceOvertimeMinutes(int $dailyWorkedMinutes, ?string $date = null): int
    {
        if ($date) {
            $d = Carbon::parse($date);
            if ($d->isFriday() || $d->isSaturday()) {
                return $dailyWorkedMinutes;
            }
        }

        if ($dailyWorkedMinutes < 535) {
            return 0;
        }

        $extraAfterNine = $dailyWorkedMinutes - 535;
        $blocks = intdiv($extraAfterNine, 15);

        return 60 + ($blocks * 15);
    }

    protected function timeToMinutes(?string $time): int
    {
        if (! $time) {
            return 0;
        }

        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return 0;
        }

        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return $h * 60 + $m;
    }

    protected function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%s%02d:%02d', $sign, $hours, $mins);
    }
}
