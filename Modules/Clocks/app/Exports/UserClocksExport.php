<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Clocks\Exports\Sheets\UserClocksDetailedSheet;
use Modules\Clocks\Exports\Sheets\UserClocksSummarySheet;
use Modules\Clocks\Exports\Sheets\UserClocksPlanSheet;
use Modules\Clocks\Models\UserClockOvertime;
use Modules\Users\Models\User;
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
    protected array $rowStyles = [];
    protected array $ruleLegend = [];
    protected array $defaultCategoryColors = [
        'lateness' => 'FFC7CE',
        'deduction' => 'FFC7CE',
        'shortfall' => 'FFC7CE',
        'default_shortfall' => 'FFC7CE',
        'overtime' => 'C6EFCE',
        'vacation' => 'BDD7EE',
        'issue' => 'FCE4D6',
        'bonus' => 'C6EFCE',
        'other' => 'D9D9D9',
    ];

    public function __construct($users, $startDate = null, $endDate = null)
    {
        $this->users = $this->normalizeUsers($users);
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->planRows = collect();
        $this->ruleLegend = [];

        $this->prepareData();
    }

    public function sheets(): array
    {
        return [
            new UserClocksSummarySheet($this->summaryRows),
            new UserClocksPlanSheet($this->planRows, $this->ruleLegend),
            new UserClocksDetailedSheet($this->detailedRows, $this->rowStyles),
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

    public function getRowStyles(): array
    {
        return $this->rowStyles;
    }

    protected function prepareData(): void
    {
        $this->detailedRows = collect();
        $this->summaryRows = collect();
        $this->planRows = collect();
        $this->rowStyles = [];
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
        ];

        foreach ($this->users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $resolvedPlan = $planResolver->resolveForUser($user);
            $deductionEngine = new DeductionRuleEngine($resolvedPlan);
            $planGraceMinutes = $resolvedPlan['grace_minutes'] ?? 15;
            $ruleState = [];
            $userPlanRowStart = $this->planRows->count();

            foreach ($resolvedPlan['rules'] as $planRule) {
                $categoryKey = $planRule['category'] ?? 'other';
                $color = ltrim($planRule['color'] ?? '', '#');
                if ($color !== '') {
                    $this->ruleLegend[$categoryKey] = strtoupper($color);
                }
            }

            $timezoneValue = optional($user->timezone)->value ?? 3;
            $department = optional($user->department);
            $workScheduleType = strtolower($department->work_schedule_type ?? 'flexible');
            $isFlexibleSchedule = $workScheduleType !== 'strict';
            $worksOnSaturday = (bool) ($department?->works_on_saturday ?? false);
            $userDetail = optional($user->user_detail);
            $workingHoursDay = $userDetail->working_hours_day ?? 8;
            if ($workingHoursDay <= 0) {
                $workingHoursDay = 8;
            }
            $requiredMinutesPerDay = (int) round($workingHoursDay * 60);
            $scheduledStartTime = $userDetail->start_time ?? '08:00:00';

            $now = Carbon::now();
            $defaultStartDate = $now->copy()->subMonth()->day(26)->startOfDay();
            $defaultEndDate = $now->copy()->day(26)->endOfDay();

            $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : $defaultStartDate->copy();
            $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : $defaultEndDate->copy();

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

            $grouped = $clocks->groupBy(function ($clock) {
                return Carbon::parse($clock->clock_in)->format('Y-m-d');
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

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $formattedDate = $date->format('Y-m-d');
                $dailyClocks = $grouped->get($formattedDate, collect());
                $isVacationDay = $user->user_vacations()->where('status', 'approved')
                    ->whereDate('from_date', '<=', $formattedDate)
                    ->whereDate('to_date', '>=', $formattedDate)
                    ->exists();

                if ($dailyClocks->isEmpty()) {
                    if ($isVacationDay) {
                        $totalVacationDays++;
                    }
                    continue;
                }

                $hasIssue = false;
                $issueColumns = [];                $workedMinutes = 0;
                $earliestIn = null;
                $latestOut = null;
                $firstLocIn = '';
                $lastLocOut = '';
                $segments = [];

                if ($dailyClocks->isNotEmpty()) {
                    foreach ($dailyClocks as $clock) {
                        $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                        $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;

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
                            'in' => $clockIn ? $clockIn->copy()->addHours($timezoneValue)->format('h:i A') : '',
                            'out' => $clockOut ? $clockOut->copy()->addHours($timezoneValue)->format('h:i A') : '',
                        ];
                    }
                }

                $isFriday = $date->isFriday();
                $isSaturday = $date->isSaturday();
                $isNonWorkingWeekend = $isFriday || ($isSaturday && ! $worksOnSaturday);

                $requiredMinutes = $isNonWorkingWeekend ? 0 : $requiredMinutesPerDay;
                $totalRequiredMinutes += $requiredMinutes;

                if ($hasIssue) {
                    $totalIssueDays++;
                    $requiredMinutes = 0;
                }

                $latenessBeyondGrace = 0;
                $latenessMinutesActual = 0;
                if ($earliestIn && $requiredMinutes > 0) {
                    if ($isFlexibleSchedule) {
                        $baselineStart = Carbon::parse($formattedDate . ' 09:00:00');
                    } else {
                        $baselineStart = Carbon::parse($formattedDate . ' ' . $scheduledStartTime);
                    }

                    $graceEnd = $baselineStart->copy()->addMinutes($planGraceMinutes);

                    $diffActual = $baselineStart->diffInMinutes($earliestIn, false);
                    if ($diffActual > 0) {
                        $latenessMinutesActual = $diffActual;
                    }

                    if ($earliestIn->greaterThan($graceEnd)) {
                        $latenessBeyondGrace = $graceEnd->diffInMinutes($earliestIn);
                    }
                }

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

                $shortfall = max(0, $requiredMinutes - $workedMinutes);

                $evaluationMetrics = [
    'date' => $formattedDate,
    'day_of_week' => strtolower($date->format('l')),
    'required_minutes' => $requiredMinutes,
    'default_daily_minutes' => $requiredMinutesPerDay,
    'worked_minutes' => $workedMinutes,
    'shortfall_minutes' => $shortfall,
    'lateness_minutes_actual' => $latenessMinutesActual ?? 0,
    'lateness_beyond_grace' => $latenessBeyondGrace,
    'attendance_overtime_minutes' => $attendanceOvertimeMinutes,
    'recorded_ot_minutes' => $recordedOtMinutes,
    'is_issue' => $hasIssue,
    'is_vacation' => (bool) $isVacationDay, // ✅ use the already-known flag
];


                $evaluation = $deductionEngine->evaluate($evaluationMetrics, $ruleState);
                $appliedRules = $evaluation['applied_rules'] ?? [];
                $planMonetaryDeduction = $evaluation['monetary_amount'] ?? 0.0;
                $rawDeductionMinutes = $hasIssue ? 0 : (int) ($evaluation['deduction_minutes'] ?? 0);

                if ($hasIssue) {
                    $latenessBeyondGrace = 0;
                } elseif ($rawDeductionMinutes === 0) {
                    $latenessBeyondGrace = 0;
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

                $isVacation = $isVacationDay ? 1 : 0;
                if ($isVacation) {
                    $totalVacationDays++;
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
                        'Clock In' => $dailyClocks->count() > 1 ? '' : ($earliestIn ? $earliestIn->copy()->addHours($timezoneValue)->format('h:i A') : ''),
                        'Clock Out' => $dailyClocks->count() > 1 ? '' : ($latestOut ? $latestOut->copy()->addHours($timezoneValue)->format('h:i A') : ''),
                        'Code' => $user->code,
                        'Department' => $user->department?->name ?? 'N/A',
                        'Total Hours in That Day' => $this->formatMinutes($workedMinutes),
                        'Total Over time in That Day' => $this->formatMinutes($recordedOtMinutes),
                        'Plan Deduction in That Day' => $this->formatMinutes($rawDeductionMinutes),
                        'Deduction Details' => $deductionDetailSummary,
                        'Excuse Deducted in That Day' => '',
                        'Excuse Remaining (Policy 4h)' => '',
                        'Total Excuses in That Day' => $this->formatMinutes($dailyExcuseMinutes),
                        'Is this date has vacation' => $isVacation === 0 ? 'NO' : 'YES',
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
                    'is_vacation' => $isVacation === 1,
                    'issue_columns' => $issueColumns,
                ];
            }

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
                $totalExcuseMinutesApplied += $entry['excuse_applied_minutes'];
                $totalChargeableDeductionMinutes += $entry['chargeable_deduction_minutes'];

                $row = $entry['row_data'];
                $row['Excuse Deducted in That Day'] = $this->formatMinutes($entry['excuse_applied_minutes']);
                $row['Excuse Remaining (Policy 4h)'] = '';

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
                'Required %s, worked %s, shortfall beyond grace %s. Late beyond grace not recouped: %s. Excuse tokens used: %d. Chargeable deduction %s.',
                $this->formatMinutes($totalRequiredMinutes),
                $this->formatMinutes($totalWorkedMinutes),
                $this->formatMinutes($totalRawDeductionMinutes),
                $this->formatMinutes($totalLatenessBeyondGrace),
                $excuseTokensUsed,
                $this->formatMinutes($totalChargeableDeductionMinutes)
            );

            $this->summaryRows->push([
                'Employee' => $user->name,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? 'N/A',
                'Total Worked Hours' => $this->formatMinutes($totalWorkedMinutes),
                'Total OT Hours' => $this->formatMinutes($totalRecordedOtMinutes),
                'Total Attendance OT Hours' => $this->formatMinutes($totalAttendanceOtMinutes),
                'Raw Deduction Hours' => $this->formatMinutes($totalRawDeductionMinutes),
                'Excuse Hours Used' => $this->formatMinutes($totalExcuseMinutesApplied),
                'Chargeable Deduction Hours' => $this->formatMinutes($totalChargeableDeductionMinutes),
                'Issue Days' => $totalIssueDays,
                'Vacation Days' => $totalVacationDays,
                'Base Salary' => $baseSalary !== null ? round($baseSalary, 2) : null,
                'Hourly Rate' => $hourlyRate !== null ? round($hourlyRate, 2) : null,
                'Worked Pay' => $workedPay !== null ? round($workedPay, 2) : null,
                'OT Rate' => $overtimeRate !== null ? round($overtimeRate, 2) : null,
                'OT Pay' => $otPay !== null ? round($otPay, 2) : null,
                'Gross Pay' => $grossPay !== 0.0 ? round($grossPay, 2) : null,
                'Deduction Amount' => $deductionAmount !== null ? round($deductionAmount, 2) : null,
                'Net Pay' => $netPay !== 0.0 ? round($netPay, 2) : null,
                'Notes' => $notes,
            ]);

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
            $overallTotals['net_pay'] += $netPay;
            $overallTotals['excuse_tokens_used'] += $excuseTokensUsed;
            $overallTotals['lateness_beyond_grace_minutes'] += $totalLatenessBeyondGrace;
            $overallTotals['issue_days'] = ($overallTotals['issue_days'] ?? 0) + $totalIssueDays;
        }

        if ($this->summaryRows->isNotEmpty()) {
            $notes = sprintf(
                'Required %s, worked %s, shortfall beyond grace %s. Late beyond grace not recouped: %s. Excuse tokens used across all employees: %d. Chargeable deduction %s. Issue days: %d.',
                $this->formatMinutes($overallTotals['required_minutes']),
                $this->formatMinutes($overallTotals['worked_minutes']),
                $this->formatMinutes($overallTotals['raw_deduction_minutes']),
                $this->formatMinutes($overallTotals['lateness_beyond_grace_minutes']),
                $overallTotals['excuse_tokens_used'],
                $this->formatMinutes($overallTotals['chargeable_deduction_minutes']),
                $overallTotals['issue_days'] ?? 0
            );

            $this->summaryRows->push([
                'Employee' => 'TOTAL',
                'Code' => '',
                'Department' => '',
                'Total Worked Hours' => $this->formatMinutes($overallTotals['worked_minutes']),
                'Total OT Hours' => $this->formatMinutes($overallTotals['recorded_ot_minutes']),
                'Total Attendance OT Hours' => $this->formatMinutes($overallTotals['attendance_ot_minutes']),
                'Raw Deduction Hours' => $this->formatMinutes($overallTotals['raw_deduction_minutes']),
                'Excuse Hours Used' => $this->formatMinutes($overallTotals['excuse_minutes']),
                'Chargeable Deduction Hours' => $this->formatMinutes($overallTotals['chargeable_deduction_minutes']),
                'Issue Days' => $overallTotals['issue_days'] ?? 0,
                'Vacation Days' => $overallTotals['vacation_days'],
                'Base Salary' => $overallTotals['base_salary'] !== 0.0 ? round($overallTotals['base_salary'], 2) : null,
                'Hourly Rate' => null,
                'Worked Pay' => $overallTotals['worked_pay'] !== 0.0 ? round($overallTotals['worked_pay'], 2) : null,
                'OT Rate' => null,
                'OT Pay' => $overallTotals['ot_pay'] !== 0.0 ? round($overallTotals['ot_pay'], 2) : null,
                'Gross Pay' => $overallTotals['gross_pay'] !== 0.0 ? round($overallTotals['gross_pay'], 2) : null,
                'Deduction Amount' => $overallTotals['deduction_amount'] !== 0.0 ? round($overallTotals['deduction_amount'], 2) : null,
                'Net Pay' => $overallTotals['net_pay'] !== 0.0 ? round($overallTotals['net_pay'], 2) : null,
                'Notes' => $notes,
            ]);
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