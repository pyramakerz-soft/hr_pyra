<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Users\Models\User;
use Modules\Clocks\Models\UserClockOvertime;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected $users;
    protected $startDate;
    protected $endDate;
    protected $rowStyles = [];
    public function __construct($users, $startDate = null, $endDate = null)
    {
        // Normalize to a collection of User models
        if ($users instanceof \Modules\Users\Models\User) {
            $this->users = collect([$users]);
        } elseif ($users instanceof \Illuminate\Support\Collection || $users instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->users = $users;
        } else {
            $this->users = collect(is_array($users) ? $users : [$users]);
        }
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $finalCollection = new Collection();
        foreach ($this->users as $user) {
            $emptyFeild = 'N/A';
            $now = Carbon::now();
            $defaultStartDate = $now->copy()->subMonth()->day(26)->startOfDay();
            $defaultEndDate = $now->copy()->day(26)->endOfDay();

            $timezoneValue = $user->timezone ? $user->timezone->value : 3;
            $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : $defaultStartDate;
            $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : $defaultEndDate;

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

            // Initialize accumulated totals
            $accumulatedTotalMinutes = 0;
            $accumulatedExcuses = 0;
            $accumulatedOverTimes = 0;
            $accumulatedAttendanceOvertime = 0;
            $accumulatedExcuseDeducted = 0;
            $accumulatedVacation = 0;

            // Excuse policy remaining bank per user across the export range
            $excuseRemaining = 240; // minutes (4 hours)

            // Loop through all dates from start to end
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $formattedDate = $date->format('Y-m-d');
                $dailyClocks = $grouped->get($formattedDate, collect());
                $formattedTotal = 0; // minutes accumulator for the day
                $maxLate = 0; // minutes
                $maxEarly = 0; // minutes
                $earliestIn = null; $latestOut = null; $firstLocIn = ''; $lastLocOut = '';
                if ($dailyClocks->isNotEmpty()) {
                    // Compute earliest/ latest and aggregate values; do not push per-entry rows
                    $earliestIn = null; $latestOut = null; $firstLocIn = ''; $lastLocOut = '';
                    foreach ($dailyClocks as $clock) {
                        $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                        $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;

                        if ($clockIn && $clockOut) {
                            $diff = $clockIn->diffInMinutes($clockOut);
                            $accumulatedTotalMinutes +=  $diff;
                            $formattedTotal += $diff;
                        }
                        // Track max late/early for this date
                        $lateStr = $clock->late_arrive ?? '00:00:00';
                        $earlyStr = $clock->early_leave ?? '00:00:00';
                        $lateMin = $this->timeToMinutes($lateStr);
                        $earlyMin = $this->timeToMinutes($earlyStr);
                        if ($lateMin > $maxLate) { $maxLate = $lateMin; }
                        if ($earlyMin > $maxEarly) { $maxEarly = $earlyMin; }

                        if ($clockIn && (!$earliestIn || $clockIn->lt($earliestIn))) {
                            $earliestIn = $clockIn->copy();
                            $firstLocIn = $clock->location_type === 'float' ? ($clock->address_clock_in ?? '')
                                : ($clock->location_type === 'home' ? 'home'
                                    : ($clock->location_type === 'site' && $clock->clock_in ? optional($clock->location)->name : ''));
                        }
                        if ($clockOut && (!$latestOut || $clockOut->gt($latestOut))) {
                            $latestOut = $clockOut->copy();
                            $lastLocOut = $clock->location_type === 'float' ? ($clock->address_clock_out ?? '')
                                : ($clock->location_type === 'home' ? 'home'
                                    : ($clock->location_type === 'site' && $clock->clock_out ? optional($clock->location)->name : ''));
                        }
                    }
                } else {
                    // No clocks in this day → add empty row
                    // do nothing here; a single daily summary row will be added below
                }

                // Daily Excuses
                $dailyExcuses = $user->excuses()->where('status', 'approved')
                    ->whereDate('date', $formattedDate)
                    ->get()
                    ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
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

                $otStatus = null;
                if ($recordedMinutes > 0) {
                    $statusSource = $overtimeRecord?->overall_status ?? 'pending';
                    $otStatus = strtolower($statusSource);
                } elseif ($attendanceOvertimeMin > 0) {
                    $otStatus = 'pending';
                }

                // Excuse policy: deduct up to 2h per day from either late or early (not both), total cap 4h across range
                $candidateExcuse = min(max($maxLate, $maxEarly), 120);
                $deductToday = min($candidateExcuse, $excuseRemaining);
                $excuseRemaining -= $deductToday;
                $accumulatedExcuseDeducted += $deductToday;
                $formattedDeductToday = sprintf('%02d:%02d', floor($deductToday / 60), $deductToday % 60);
                $formattedRemaining = sprintf('%02d:%02d', floor($excuseRemaining / 60), $excuseRemaining % 60);

                // Check vacation
                $isVacation = $user->user_vacations()->where('status', 'approved')
                    ->whereDate('from_date', '<=', $formattedDate)
                    ->whereDate('to_date', '>=', $formattedDate)
                    ->exists() ? 1 : 0;
                $accumulatedVacation  += $isVacation;

                $totalHoursInSpecificDay = sprintf('%02d:%02d', floor($formattedTotal / 60), $formattedTotal % 60);

                // Build single daily row in the requested order
                $hasMultipleSegments = $dailyClocks->count() > 1;
                $clockInFormatted = $hasMultipleSegments ? '' : ($earliestIn ? $earliestIn->copy()->addHours($timezoneValue)->format('h:i A') : '');
                $clockOutFormatted = $hasMultipleSegments ? '' : ($latestOut ? $latestOut->copy()->addHours($timezoneValue)->format('h:i A') : '');
                // Mark row styles to apply later in styles()
                $rowIndex = 1 + $finalCollection->count() + 1; // header + current size + this row
                $isWeekend = Carbon::parse($formattedDate)->isFriday() || Carbon::parse($formattedDate)->isSaturday();
                $this->rowStyles[] = [
                    'row' => $rowIndex,
                    'ot_status' => $otStatus,
                    'weekend' => $isWeekend,
                ];
                $finalCollection->push([
                    'Date' => $formattedDate,
                    'Name' => $user->name,
                    'Clock In' =>  $clockInFormatted,
                    'Clock Out' =>  $clockOutFormatted,
                    'Code' =>  $user->code,
                    'Department' =>  $user->department?->name ?? $emptyFeild,
                    'Total Hours in That Day' =>  $totalHoursInSpecificDay,
                    'Total Over time in That Day' => $formattedOverTimes,
                    'Excuse Deducted in That Day' => $formattedDeductToday,
                    'Excuse Remaining (Policy 4h)' => $formattedRemaining,
                    'Total Excuses in That Day' => $formattedExcuses,
                    'Is this date has vacation' => $isVacation == 0 ? 'NO' : 'YES',
                    'Location In' =>  $firstLocIn,
                    'Location Out' =>  $lastLocOut,
                    'Attendance Over time in That Day' => $formattedAttendanceOT,
                ]);

                // Add extra rows for additional segments (only Clock In/Out shown)
                if ($hasMultipleSegments) {
                    foreach ($dailyClocks as $seg) {
                        $segmentRowIndex = 1 + $finalCollection->count() + 1;
                        $segIn = $seg->clock_in ? Carbon::parse($seg->clock_in)->addHours($timezoneValue)->format('h:i A') : '';
                        $segOut = $seg->clock_out ? Carbon::parse($seg->clock_out)->addHours($timezoneValue)->format('h:i A') : '';
                        $finalCollection->push([
                            'Date' => '',
                            'Name' =>  '',
                            'Clock In' =>  $segIn,
                            'Clock Out' =>  $segOut,
                            'Code' =>  '',
                            'Department' =>  '',
                            'Total Hours in That Day' =>  '',
                            'Total Over time in That Day' => '',
                            'Excuse Deducted in That Day' => '',
                            'Excuse Remaining (Policy 4h)' => '',
                            'Total Excuses in That Day' =>  '',
                            'Is this date has vacation' =>  '',
                            'Location In' =>  '',
                            'Location Out' =>  '',
                            'Attendance Over time in That Day' => '',
                        ]);

                        $this->rowStyles[] = [
                            'row' => $segmentRowIndex,
                            'multi_segment' => true,
                        ];
                    }
                }

                // Empty row between days
                $finalCollection->push([
                    'Date' => '',
                    'Name' =>  '',
                    'Clock In' =>   '',
                    'Clock Out' =>  '',
                    'Code' =>  '',
                    'Department' =>  '',
                    'Total Hours in That Day' =>  '',
                    'Total Over time in That Day' => '',
                    'Excuse Deducted in That Day' => '',
                    'Excuse Remaining (Policy 4h)' => '',
                    'Total Excuses in That Day' =>   '',
                    'Is this date has vacation' =>   '',
                    'Location In' =>  '',
                    'Location Out' =>  '',
                    'Attendance Over time in That Day' => '',
                ]);
            }

            // Add the summary row for accumulated totals for this user
            $finalCollection->push([
                'Date' => '---TOTAL for '.$user->name.'----',
                'Name' => $user->name,
                'Clock In' => $emptyFeild,
                'Clock Out' => $emptyFeild,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? $emptyFeild,
                'Total Hours in That Day' => sprintf('%02d:%02d', floor($accumulatedTotalMinutes / 60), $accumulatedTotalMinutes % 60),
                'Total Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedOverTimes / 60), $accumulatedOverTimes % 60),
                'Excuse Deducted in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuseDeducted / 60), $accumulatedExcuseDeducted % 60),
                'Excuse Remaining (Policy 4h)' => sprintf('%02d:%02d', floor($excuseRemaining / 60), $excuseRemaining % 60),
                'Total Excuses in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuses / 60), $accumulatedExcuses % 60),
                'Is this date has vacation' =>  $accumulatedVacation == '0' ? 'NO VACATIONS' : $accumulatedVacation . '  Days ',
                'Location In' => $emptyFeild,
                'Location Out' => $emptyFeild,
                'Attendance Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedAttendanceOvertime / 60), $accumulatedAttendanceOvertime % 60),
            ]);

            // Separator between users
            $finalCollection->push([
                'Date' => '--------------------',
                'Name' =>  '',
                'Clock In' =>   '',
                'Clock Out' =>  '',
                'Code' =>  '',
                'Department' =>  '',
                'Total Hours in That Day' =>  '',
                'Total Over time in That Day' => '',
                'Excuse Deducted in That Day' => '',
                'Excuse Remaining (Policy 4h)' => '',
                'Total Excuses in That Day' =>   '',
                'Is this date has vacation' =>   '',
                'Location In' =>  '',
                'Location Out' =>  '',
                'Attendance Over time in That Day' => '',
            ]);
        }
        return $finalCollection;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Clock In',
            'Clock Out',
            'Code',
            'Department',
            'Total Hours in That Day',
            'Total Over time in That Day',
            'Excuse Deducted in That Day',
            'Excuse Remaining (Policy 4h)',
            'Total Excuses in That Day',
            'Is this date has vacation',
            'Location In',
            'Location Out',
            'Attendance Over time in That Day',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ]);
        // Set column widths
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setWidth(30);
        }
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->setAutoFilter('A1:O1');

        // Highlight rules
        $otStatusColors = [
            'pending' => 'FFF2CC',
            'declined' => 'FFC7CE',
            'approved' => 'F4B183',
        ];
        $weekendColor = 'BDD7EE'; // Fri/Sat: light blue
        $multiColor = 'C6E0B4';   // Multiple segments: light green

        foreach ($this->rowStyles as $mark) {
            $r = $mark['row'] ?? null;
            if (!$r) { continue; }

            if (!empty($mark['ot_status'])) {
                $statusKey = strtolower($mark['ot_status']);
                if (isset($otStatusColors[$statusKey])) {
                    foreach (['H','O'] as $col) {
                        $sheet->getStyle($col.$r)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $otStatusColors[$statusKey]],
                            ],
                        ]);
                    }
                }
            }

            if (!empty($mark['weekend'])) {
                $sheet->getStyle('A'.$r)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $weekendColor],
                    ],
                ]);
            }

            if (!empty($mark['multi_segment'])) {
                foreach (['C', 'D'] as $col) {
                    $sheet->getStyle($col.$r)->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $multiColor],
                        ],
                    ]);
                }
            }
        }
    }

    public function columnFormats(): array
    {
        return [];
    }

    protected function computeAttendanceOvertimeMinutes(int $dailyWorkedMinutes, ?string $date = null): int
    {
        // Weekend (Friday/Saturday): all worked minutes are overtime
        if ($date) {
            $d = Carbon::parse($date);
            if ($d->isFriday() || $d->isSaturday()) {
                return $dailyWorkedMinutes;
            }
        }
        // Weekdays: no overtime until 9 hours (540 minutes)
        if ($dailyWorkedMinutes < 535) {
            return 0;
        }
        // First hour granted at 9:00, then 15-min steps afterwards
        $extraAfterNine = $dailyWorkedMinutes - 535;
        $blocks = intdiv($extraAfterNine, 15);
        return 60 + ($blocks * 15);
    }

    protected function timeToMinutes(?string $time): int
    {
        if (!$time) { return 0; }
        $parts = explode(':', $time);
        if (count($parts) < 2) { return 0; }
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        return $h * 60 + $m;
    }
}




