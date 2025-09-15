<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Users\Models\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksSheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle, WithEvents, WithCustomStartCell
{
    protected User $user;
    protected $startDate;
    protected $endDate;
    protected array $rowStyles = [];
    protected array $salarySummary = [];

    public function __construct(User $user, $startDate = null, $endDate = null)
    {
        $this->user = $user;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $user = $this->user;
        $final = new Collection();
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

        $grouped = $clocks->groupBy(fn($c) => Carbon::parse($c->clock_in)->format('Y-m-d'));

        $accumulatedTotalMinutes = 0;
        $accOT = 0;
        $accExcuses = 0;
        $accExcuseDeducted = 0;
        $accVacation = 0;
        $excuseRemaining = 240; // minutes across range

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            /** @var EloquentCollection $dailyClocks */
            $dailyClocks = $grouped->get($formattedDate, collect());

            $formattedTotal = 0; // minutes in day
            $maxLate = 0; $maxEarly = 0;
            $earliestIn = null; $latestOut = null; $firstLocIn = ''; $lastLocOut = '';

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
                    if ($lateMin > $maxLate) $maxLate = $lateMin;
                    if ($earlyMin > $maxEarly) $maxEarly = $earlyMin;

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
            }

            // Daily excuses (approved)
            $dailyExcuses = $user->excuses()->where('status', 'approved')
                ->whereDate('date', $formattedDate)
                ->get()
                ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
            $accExcuses += $dailyExcuses;
            $formattedExcuses = sprintf('%02d:%02d', floor($dailyExcuses/60), $dailyExcuses%60);

            // Attendance OT
            $attendanceOvertimeMin = $this->computeAttendanceOvertimeMinutes($formattedTotal, $formattedDate);
            $accOT += $attendanceOvertimeMin;
            $formattedOverTimes = sprintf('%02d:%02d', floor($attendanceOvertimeMin/60), $attendanceOvertimeMin%60);
            $formattedAttendanceOT = $formattedOverTimes;

            // Excuse policy
            $candidateExcuse = min(max($maxLate, $maxEarly), 120);
            $deductToday = min($candidateExcuse, $excuseRemaining);
            $excuseRemaining -= $deductToday;
            $accExcuseDeducted += $deductToday;
            $formattedDeductToday = sprintf('%02d:%02d', floor($deductToday/60), $deductToday%60);
            $formattedRemaining = sprintf('%02d:%02d', floor($excuseRemaining/60), $excuseRemaining%60);

            // Vacation
            $isVacation = $user->user_vacations()->where('status', 'approved')
                ->whereDate('from_date', '<=', $formattedDate)
                ->whereDate('to_date', '>=', $formattedDate)
                ->exists() ? 1 : 0;
            $accVacation += $isVacation;

            $hasMultipleSegments = $dailyClocks->count() > 1;
            $clockInFormatted = $hasMultipleSegments ? '' : ($earliestIn ? $earliestIn->copy()->addHours($timezoneValue)->format('h:i A') : '');
            $clockOutFormatted = $hasMultipleSegments ? '' : ($latestOut ? $latestOut->copy()->addHours($timezoneValue)->format('h:i A') : '');

            // Row index for styling: headings at row 2, so first data row is row 3
            $rowIndex = 3 + $final->count();
            $isWeekend = Carbon::parse($formattedDate)->isFriday() || Carbon::parse($formattedDate)->isSaturday();
            $this->rowStyles[] = [
                'row' => $rowIndex,
                'ot' => $attendanceOvertimeMin > 0,
                'weekend' => $isWeekend,
                'multi' => $hasMultipleSegments,
            ];

            $totalHoursInDay = sprintf('%02d:%02d', floor($formattedTotal/60), $formattedTotal%60);
            $final->push([
                'Date' => $formattedDate,
                'Clock In' => $clockInFormatted,
                'Clock Out' => $clockOutFormatted,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? 'N/A',
                'Total Hours in That Day' => $totalHoursInDay,
                'Total Over time in That Day' => $formattedOverTimes,
                'Excuse Deducted in That Day' => $formattedDeductToday,
                'Excuse Remaining (Policy 4h)' => $formattedRemaining,
                'Total Excuses in That Day' => $formattedExcuses,
                'Is this date has vacation' => $isVacation == 0 ? 'NO' : 'YES',
                'Location In' => $firstLocIn,
                'Location Out' => $lastLocOut,
                'Attendance Over time in That Day' => $formattedAttendanceOT,
            ]);

            if ($hasMultipleSegments) {
                foreach ($dailyClocks as $seg) {
                    $segIn = $seg->clock_in ? Carbon::parse($seg->clock_in)->addHours($timezoneValue)->format('h:i A') : '';
                    $segOut = $seg->clock_out ? Carbon::parse($seg->clock_out)->addHours($timezoneValue)->format('h:i A') : '';
                    $final->push([
                        'Date' => '',
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
                }
            }

            // Spacer row
            $final->push([
                'Date' => '',
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
        }

        // Salary summary (interpreting salary as hourly base rate)
        $hourly = (float)($user->salary ?? 0);
        $otRate = (float)($user->overtime_rate ?? 1.5);
        $baseMinutes = max(0, $accumulatedTotalMinutes - $accOT);
        $basePay = ($baseMinutes/60) * $hourly;
        $otPay = ($accOT/60) * $hourly * $otRate;
        $totalPay = $basePay + $otPay;

        $this->salarySummary = [
            'worked_minutes' => $accumulatedTotalMinutes,
            'ot_minutes' => $accOT,
            'hourly' => $hourly,
            'ot_rate' => $otRate,
            'base_pay' => $basePay,
            'ot_pay' => $otPay,
            'total_pay' => $totalPay,
        ];

        // Totals row
        $final->push([
            'Date' => '---TOTAL for '.$user->name.'----',
            'Clock In' => '',
            'Clock Out' => '',
            'Code' => $user->code,
            'Department' => $user->department?->name ?? 'N/A',
            'Total Hours in That Day' => sprintf('%02d:%02d', floor($accumulatedTotalMinutes/60), $accumulatedTotalMinutes%60),
            'Total Over time in That Day' => sprintf('%02d:%02d', floor($accOT/60), $accOT%60),
            'Excuse Deducted in That Day' => '',
            'Excuse Remaining (Policy 4h)' => '',
            'Total Excuses in That Day' => sprintf('%02d:%02d', floor($accExcuses/60), $accExcuses%60),
            'Is this date has vacation' => $accVacation == 0 ? 'NO VACATIONS' : $accVacation.' Days',
            'Location In' => '',
            'Location Out' => '',
            'Attendance Over time in That Day' => '',
        ]);

        // Salary summary rows
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'Salary Summary', 'Department' => '', 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'Hourly Rate', 'Department' => number_format($hourly,2), 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'OT Rate', 'Department' => $otRate, 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'Base Pay', 'Department' => number_format($basePay,2), 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'OT Pay', 'Department' => number_format($otPay,2), 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);
        $final->push(['Date' => '', 'Clock In' => '', 'Clock Out' => '', 'Code' => 'Total Pay', 'Department' => number_format($totalPay,2), 'Total Hours in That Day' => '', 'Total Over time in That Day' => '', 'Excuse Deducted in That Day' => '', 'Excuse Remaining (Policy 4h)' => '', 'Total Excuses in That Day' => '', 'Is this date has vacation' => '', 'Location In' => '', 'Location Out' => '', 'Attendance Over time in That Day' => '']);

        return $final;
    }

    public function headings(): array
    {
        return [
            'Date',
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
        // Header row at row 2 (since title at row 1)
        $sheet->getStyle('A2:O2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
        ]);
        foreach (range('A', 'O') as $col) { $sheet->getColumnDimension($col)->setWidth(22); }
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->setAutoFilter('A2:O2');

        // Coloring tags
        $otColor = 'F8CBAD';      // light orange
        $weekendColor = 'BDD7EE'; // light blue
        $multiColor = 'C6E0B4';   // light green
        foreach ($this->rowStyles as $mark) {
            $r = (int)($mark['row'] ?? 0);
            if ($r <= 0) continue;
            // row numbers already target the correct data row (3 + index)
            if (!empty($mark['ot'])) {
                foreach (['G','N'] as $col) {
                    $sheet->getStyle($col.$r)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $otColor]]
                    ]);
                }
            }
            if (!empty($mark['weekend'])) {
                $sheet->getStyle('A'.$r)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $weekendColor]]
                ]);
            }
            if (!empty($mark['multi'])) {
                $sheet->getStyle('B'.$r)->applyFromArray([
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $multiColor]]
                ]);
            }
        }
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function title(): string
    {
        $title = trim(($this->user->code ? $this->user->code.' - ' : '').($this->user->name ?? 'Employee'));
        return mb_substr($title, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $title = 'Employee: '.($this->user->name ?? '').'  |  Code: '.($this->user->code ?? '').'  |  Department: '.($this->user->department->name ?? 'N/A');
                $sheet->setCellValue('A1', $title);
                $sheet->mergeCells('A1:O1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
                ]);
            }
        ];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    protected function computeAttendanceOvertimeMinutes(int $dailyWorkedMinutes, ?string $date = null): int
    {
        if ($date) {
            $d = Carbon::parse($date);
            if ($d->isFriday() || $d->isSaturday()) {
                return $dailyWorkedMinutes;
            }
        }
        if ($dailyWorkedMinutes < 540) { return 0; }
        $extraAfterNine = $dailyWorkedMinutes - 540;
        $blocks = intdiv($extraAfterNine, 15);
        return 60 + ($blocks * 15);
    }

    protected function timeToMinutes(?string $time): int
    {
        if (!$time) return 0;
        $parts = explode(':', $time);
        if (count($parts) < 2) return 0;
        return ((int)($parts[0] ?? 0))*60 + ((int)($parts[1] ?? 0));
    }
}
