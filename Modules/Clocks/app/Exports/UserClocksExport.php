<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Clocks\Exports\Sheets\UserClocksDetailedSheet;
use Modules\Clocks\Exports\Sheets\UserClocksSummarySheet;
use Modules\Clocks\Models\UserClockOvertime;
use Modules\Users\Models\User;

class UserClocksExport implements WithMultipleSheets
{
    use Exportable;

    protected Collection $users;
    protected $startDate;
    protected $endDate;

    protected Collection $detailedRows;
    protected Collection $summaryRows;
    protected array $rowStyles = [];

    public function __construct($users, $startDate = null, $endDate = null)
    {
        $this->users = $this->normalizeUsers($users);
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $this->prepareData();
    }

    public function sheets(): array
    {
        return [
            new UserClocksSummarySheet($this->summaryRows),
            new UserClocksDetailedSheet($this->detailedRows, $this->rowStyles),
        ];
    }

    protected function prepareData(): void
    {
        $this->detailedRows = collect();
        $this->summaryRows = collect();
        $this->rowStyles = [];

        $overallTotals = [
            'worked_minutes' => 0,
            'recorded_ot_minutes' => 0,
            'attendance_ot_minutes' => 0,
            'excuse_deducted_minutes' => 0,
            'vacation_days' => 0,
            'base_salary' => 0.0,
            'worked_pay' => 0.0,
            'ot_pay' => 0.0,
            'computed_salary' => 0.0,
            'deductions' => 0.0,
            'net_pay' => 0.0,
        ];

        foreach ($this->users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $emptyField = 'N/A';
            $now = Carbon::now();
            $defaultStartDate = (clone $now)->subMonth()->day(26)->startOfDay();
            $defaultEndDate = (clone $now)->day(26)->endOfDay();

            $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : $defaultStartDate->copy();
            $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : $defaultEndDate->copy();

            $timezoneValue = optional($user->timezone)->value ?? 3;

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

            $accumulatedTotalMinutes = 0;
            $accumulatedExcuses = 0;
            $accumulatedOverTimes = 0;
            $accumulatedAttendanceOvertime = 0;
            $accumulatedExcuseDeducted = 0;
            $accumulatedVacation = 0;

            $excuseRemaining = 240;

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $formattedDate = $date->format('Y-m-d');
                $dailyClocks = $grouped->get($formattedDate, collect());
                $formattedTotal = 0;
                $maxLate = 0;
                $maxEarly = 0;
                $earliestIn = null;
                $latestOut = null;
                $firstLocIn = '';
                $lastLocOut = '';

                if ($dailyClocks->isNotEmpty()) {
                    foreach ($dailyClocks as $clock) {
                        $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                        $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;

                        if ($clockIn && $clockOut) {
                            $diff = $clockIn->diffInMinutes($clockOut);
                            $accumulatedTotalMinutes += $diff;
                            $formattedTotal += $diff;
                        }

                        $lateMin = $this->timeToMinutes($clock->late_arrive ?? '00:00:00');
                        $earlyMin = $this->timeToMinutes($clock->early_leave ?? '00:00:00');
                        if ($lateMin > $maxLate) {
                            $maxLate = $lateMin;
                        }
                        if ($earlyMin > $maxEarly) {
                            $maxEarly = $earlyMin;
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
                    }
                }

                $dailyExcuses = $user->excuses()->where('status', 'approved')
                    ->whereDate('date', $formattedDate)
                    ->get()
                    ->sum(fn ($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
                $formattedExcuses = sprintf('%02d:%02d', floor($dailyExcuses / 60), $dailyExcuses % 60);
                $accumulatedExcuses += $dailyExcuses;

                $attendanceOvertimeMin = $this->computeAttendanceOvertimeMinutes($formattedTotal, $formattedDate);

                $overtimeRecord = $overtimeRecords->get($formattedDate);
                $recordedMinutes = $overtimeRecord ? (int) $overtimeRecord->overtime_minutes : null;
                if ($recordedMinutes === null) {
                    $recordedMinutes = $attendanceOvertimeMin;
                }

                $formattedOverTimes = sprintf('%02d:%02d', floor($recordedMinutes / 60), $recordedMinutes % 60);
                $accumulatedOverTimes += $recordedMinutes;

                $formattedAttendanceOT = sprintf('%02d:%02d', floor($attendanceOvertimeMin / 60), $attendanceOvertimeMin % 60);
                $accumulatedAttendanceOvertime += $attendanceOvertimeMin;

                $candidateExcuse = min(max($maxLate, $maxEarly), 120);
                $deductToday = min($candidateExcuse, $excuseRemaining);
                $excuseRemaining -= $deductToday;
                $accumulatedExcuseDeducted += $deductToday;
                $formattedDeductToday = sprintf('%02d:%02d', floor($deductToday / 60), $deductToday % 60);
                $formattedRemaining = sprintf('%02d:%02d', floor($excuseRemaining / 60), $excuseRemaining % 60);

                $isVacation = $user->user_vacations()->where('status', 'approved')
                    ->whereDate('from_date', '<=', $formattedDate)
                    ->whereDate('to_date', '>=', $formattedDate)
                    ->exists() ? 1 : 0;
                $accumulatedVacation += $isVacation;

                $totalHoursInSpecificDay = sprintf('%02d:%02d', floor($formattedTotal / 60), $formattedTotal % 60);

                $hasMultipleSegments = $dailyClocks->count() > 1;
                $clockInFormatted = $hasMultipleSegments ? '' : ($earliestIn ? $earliestIn->copy()->addHours($timezoneValue)->format('h:i A') : '');
                $clockOutFormatted = $hasMultipleSegments ? '' : ($latestOut ? $latestOut->copy()->addHours($timezoneValue)->format('h:i A') : '');

                $rowIndex = $this->detailedRows->count() + 2;
                $isWeekend = Carbon::parse($formattedDate)->isFriday() || Carbon::parse($formattedDate)->isSaturday();
                $otStatus = null;
                if ($recordedMinutes > 0) {
                    $statusSource = $overtimeRecord?->overall_status ?? 'pending';
                    $otStatus = strtolower($statusSource);
                } elseif ($attendanceOvertimeMin > 0) {
                    $otStatus = 'pending';
                }

                $this->rowStyles[] = [
                    'row' => $rowIndex,
                    'ot_status' => $otStatus,
                    'weekend' => $isWeekend,
                ];

                $this->detailedRows->push([
                    'Date' => $formattedDate,
                    'Name' => $user->name,
                    'Clock In' => $clockInFormatted,
                    'Clock Out' => $clockOutFormatted,
                    'Code' => $user->code,
                    'Department' => $user->department?->name ?? $emptyField,
                    'Total Hours in That Day' => $totalHoursInSpecificDay,
                    'Total Over time in That Day' => $formattedOverTimes,
                    'Excuse Deducted in That Day' => $formattedDeductToday,
                    'Excuse Remaining (Policy 4h)' => $formattedRemaining,
                    'Total Excuses in That Day' => $formattedExcuses,
                    'Is this date has vacation' => $isVacation === 0 ? 'NO' : 'YES',
                    'Location In' => $firstLocIn,
                    'Location Out' => $lastLocOut,
                    'Attendance Over time in That Day' => $formattedAttendanceOT,
                ]);

                if ($hasMultipleSegments) {
                    foreach ($dailyClocks as $seg) {
                        $segmentRowIndex = $this->detailedRows->count() + 2;
                        $segIn = $seg->clock_in ? Carbon::parse($seg->clock_in)->addHours($timezoneValue)->format('h:i A') : '';
                        $segOut = $seg->clock_out ? Carbon::parse($seg->clock_out)->addHours($timezoneValue)->format('h:i A') : '';

                        $this->detailedRows->push([
                            'Date' => '',
                            'Name' => '',
                            'Clock In' => $segIn,
                            'Clock Out' => $segOut,
                            'Code' => '',
                            'Department' => '',
                            'Total Hours in That Day' => '',
                            'Total Over time in That Day' => '',
                            'Excuse Deducted in That Day' => '',
                            'Excuse Remaining (Policy 4h)' => '',
                            'Total Excuses in That Day' => '',
                            'Is this date has vacation' => '',
                            'Location In' => '',
                            'Location Out' => '',
                            'Attendance Over time in That Day' => '',
                        ]);

                        $this->rowStyles[] = [
                            'row' => $segmentRowIndex,
                            'multi_segment' => true,
                        ];
                    }
                }
            }

            $this->detailedRows->push([
                'Date' => '---TOTAL for ' . $user->name . '----',
                'Name' => $user->name,
                'Clock In' => $emptyField,
                'Clock Out' => $emptyField,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? $emptyField,
                'Total Hours in That Day' => sprintf('%02d:%02d', floor($accumulatedTotalMinutes / 60), $accumulatedTotalMinutes % 60),
                'Total Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedOverTimes / 60), $accumulatedOverTimes % 60),
                'Excuse Deducted in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuseDeducted / 60), $accumulatedExcuseDeducted % 60),
                'Excuse Remaining (Policy 4h)' => '',
                'Total Excuses in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuses / 60), $accumulatedExcuses % 60),
                'Is this date has vacation' => $accumulatedVacation === 0 ? 'NO VACATIONS' : $accumulatedVacation . ' Days',
                'Location In' => $emptyField,
                'Location Out' => $emptyField,
                'Attendance Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedAttendanceOvertime / 60), $accumulatedAttendanceOvertime % 60),
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
                'Excuse Deducted in That Day' => '',
                'Excuse Remaining (Policy 4h)' => '',
                'Total Excuses in That Day' => '',
                'Is this date has vacation' => '',
                'Location In' => '',
                'Location Out' => '',
                'Attendance Over time in That Day' => '',
            ]);

            $hourlyRate = optional($user->user_detail)->hourly_rate;
            $overtimeRate = optional($user->user_detail)->overtime_hourly_rate;
            $baseSalary = optional($user->user_detail)->salary;

            $workedHours = $accumulatedTotalMinutes / 60;
            $recordedOtHours = $accumulatedOverTimes / 60;
            $excuseDeductHours = $accumulatedExcuseDeducted / 60;

            $workedPay = $hourlyRate !== null ? round($hourlyRate * $workedHours, 2) : null;
            $otPay = $overtimeRate !== null ? round($overtimeRate * $recordedOtHours, 2) : null;
            $deductions = $hourlyRate !== null ? round($hourlyRate * $excuseDeductHours, 2) : null;

            $computedSalary = null;
            if ($baseSalary !== null || $workedPay !== null || $otPay !== null) {
                $computedSalary = ($baseSalary ?? 0) + ($workedPay ?? 0) + ($otPay ?? 0);
            }

            $netPay = null;
            if ($computedSalary !== null) {
                $netPay = $computedSalary - ($deductions ?? 0);
            }

            $this->summaryRows->push([
                'Employee' => $user->name,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? $emptyField,
                'Total Worked Hours' => $this->formatMinutes($accumulatedTotalMinutes),
                'Total OT Hours' => $this->formatMinutes($accumulatedOverTimes),
                'Total Attendance OT Hours' => $this->formatMinutes($accumulatedAttendanceOvertime),
                'Excuse Deducted Hours' => $this->formatMinutes($accumulatedExcuseDeducted),
                'Vacation Days' => $accumulatedVacation,
                'Base Salary' => $baseSalary !== null ? round($baseSalary, 2) : null,
                'Hourly Rate' => $hourlyRate !== null ? round($hourlyRate, 2) : null,
                'OT Rate' => $overtimeRate !== null ? round($overtimeRate, 2) : null,
                'Computed Salary' => $computedSalary !== null ? round($computedSalary, 2) : null,
                'Computed Deductions' => $deductions !== null ? round($deductions, 2) : null,
                'Net Pay' => $netPay !== null ? round($netPay, 2) : null,
            ]);

            $overallTotals['worked_minutes'] += $accumulatedTotalMinutes;
            $overallTotals['recorded_ot_minutes'] += $accumulatedOverTimes;
            $overallTotals['attendance_ot_minutes'] += $accumulatedAttendanceOvertime;
            $overallTotals['excuse_deducted_minutes'] += $accumulatedExcuseDeducted;
            $overallTotals['vacation_days'] += $accumulatedVacation;

            if ($baseSalary !== null) {
                $overallTotals['base_salary'] += $baseSalary;
            }
            if ($workedPay !== null) {
                $overallTotals['worked_pay'] += $workedPay;
            }
            if ($otPay !== null) {
                $overallTotals['ot_pay'] += $otPay;
            }
            if ($computedSalary !== null) {
                $overallTotals['computed_salary'] += $computedSalary;
            }
            if ($deductions !== null) {
                $overallTotals['deductions'] += $deductions;
            }
            if ($netPay !== null) {
                $overallTotals['net_pay'] += $netPay;
            }
        }

        if ($this->summaryRows->isNotEmpty()) {
            $this->summaryRows->push([
                'Employee' => 'TOTAL',
                'Code' => '',
                'Department' => '',
                'Total Worked Hours' => $this->formatMinutes($overallTotals['worked_minutes']),
                'Total OT Hours' => $this->formatMinutes($overallTotals['recorded_ot_minutes']),
                'Total Attendance OT Hours' => $this->formatMinutes($overallTotals['attendance_ot_minutes']),
                'Excuse Deducted Hours' => $this->formatMinutes($overallTotals['excuse_deducted_minutes']),
                'Vacation Days' => $overallTotals['vacation_days'],
                'Base Salary' => $overallTotals['base_salary'] !== 0.0 ? round($overallTotals['base_salary'], 2) : null,
                'Hourly Rate' => null,
                'OT Rate' => null,
                'Computed Salary' => $overallTotals['computed_salary'] !== 0.0 ? round($overallTotals['computed_salary'], 2) : null,
                'Computed Deductions' => $overallTotals['deductions'] !== 0.0 ? round($overallTotals['deductions'], 2) : null,
                'Net Pay' => $overallTotals['net_pay'] !== 0.0 ? round($overallTotals['net_pay'], 2) : null,
            ]);
        }
    }

    protected function normalizeUsers($users): Collection
    {
        if ($users instanceof User) {
            return collect([$users]);
        }

        if ($users instanceof Collection || $users instanceof EloquentCollection) {
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
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
