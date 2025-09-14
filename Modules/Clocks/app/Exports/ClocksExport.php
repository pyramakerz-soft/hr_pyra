<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ClocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected $clocks;
    protected $department;
    protected $userId;
    protected $startDate;
    protected $endDate;
    // Track remaining excuse minutes per user across the export period (policy: 4 hours total per user)
    protected $excuseRemainingByUser = [];
    // Precomputed per (user_id|date) keys
    protected $dailyTotalMinutes = [];
    protected $dailyOvertimeMinutes = [];
    protected $dailyMaxLateMinutes = [];
    protected $dailyMaxEarlyMinutes = [];
    protected $dailyExcuseDeducted = [];
    protected $dailyExcuseRemaining = [];

    public function __construct($clocks, $department = null, $startDate = null, $endDate = null)
    {
        $this->clocks = $clocks;
        $this->department = $department;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Precompute daily aggregates by user and date
        $this->precomputeDailyAggregates();

        // Ensure deterministic order for applying daily policy-based deductions
        $sorted = $this->clocks->sortBy([
            ['user_id', 'asc'],
            ['clock_in', 'asc'],
        ]);

        return $sorted->map(function ($clock) {
            // Convert clock_in and clock_out to Egypt Time (UTC+2)
            $clockIn = $clock->clock_in ?  Carbon::parse($clock->clock_in) : null;
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;

            // Check if both clock_in and clock_out are not null
            if ($clockIn && $clockOut) {
                // Calculate total hours in minutes
                $totalMinutes = $clockIn->diffInMinutes($clockOut);

                // Convert total minutes to HH:MM format
                $formattedTotalHours = sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
            } else {
                $formattedTotalHours = null; // If either is null, set totalHours to null
            }

            // Fetch user's total overtime in the provided date range
            $user = $clock->user;
            // Sum of APPROVED excuses in the provided date range (for reference)
            $totalExcuses = $user->excuses()
                ->where('status', 'approved')
                ->when($this->startDate && $this->endDate, function ($q) {
                    $q->whereBetween('date', [$this->startDate, $this->endDate]);
                })
                ->get()
                ->sum(function ($excuse) {
                    return Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to));
                });

            // Format total minutes into HH:MM for approved excuses
            $formattedExcuses = sprintf('%02d:%02d', intdiv($totalExcuses, 60), $totalExcuses % 60);

            // Daily context (by user and date)
            $dateKey = $clockIn ? $clockIn->format('Y-m-d') : null;
            $key = $dateKey ? ($clock->user_id . '|' . $dateKey) : null;

            $dailyTotal = $key && isset($this->dailyTotalMinutes[$key]) ? $this->dailyTotalMinutes[$key] : 0;
            $dailyOvertime = $key && isset($this->dailyOvertimeMinutes[$key]) ? $this->dailyOvertimeMinutes[$key] : 0;
            $maxLate = $key && isset($this->dailyMaxLateMinutes[$key]) ? $this->dailyMaxLateMinutes[$key] : 0;
            $maxEarly = $key && isset($this->dailyMaxEarlyMinutes[$key]) ? $this->dailyMaxEarlyMinutes[$key] : 0;
            $excuseDeductedToday = $key && isset($this->dailyExcuseDeducted[$key]) ? $this->dailyExcuseDeducted[$key] : 0;
            $excuseRemaining = $key && isset($this->dailyExcuseRemaining[$key]) ? $this->dailyExcuseRemaining[$key] : ($this->excuseRemainingByUser[$clock->user_id] ?? 0);

            $formattedDailyTotal = sprintf('%02d:%02d', intdiv($dailyTotal, 60), $dailyTotal % 60);
            $formattedOvertime = sprintf('%02d:%02d', intdiv($dailyOvertime, 60), $dailyOvertime % 60);
            $formattedLate = sprintf('%02d:%02d', intdiv($maxLate, 60), $maxLate % 60);
            $formattedEarly = sprintf('%02d:%02d', intdiv($maxEarly, 60), $maxEarly % 60);
            $formattedExcuseDeductedToday = sprintf('%02d:%02d', intdiv($excuseDeductedToday, 60), $excuseDeductedToday % 60);
            $formattedExcuseRemaining = sprintf('%02d:%02d', intdiv($excuseRemaining, 60), $excuseRemaining % 60);

            return collect([
                'Code' => $clock->user->code,
                'Name' => $clock->user->name,
                'Department' => $clock->user->department ? $clock->user->department->name : 'N/A',
                'Date' => $clockIn->format('Y-m-d'),
                'Clock_In' => $clockIn->format('h:iA'),  // Formatted as 12-hour time (AM/PM)
                'Clock_Out' => $clockOut ? $clockOut->format('h:iA') : null, // Same format for Clock Out
                'Total Hours (Entry)' =>   $formattedTotalHours,
                'Total Hours (Day)' => $formattedDailyTotal,
                'Overtime (Day)' => $formattedOvertime,
                'Late Arrive' => $formattedLate,
                'Early Leave' => $formattedEarly,
                'Location_In' =>
                $clock->location_type == "float" ?
                    $clock->address_clock_in  : ($clock->location_type == "home" ? "home" : ($clock->location_type == "site" && $clock->clock_in ? $clock->location->name : null)),
                'Location_Out' =>  $clock->location_type == "float" ?
                    $clock->address_clock_out  : ($clock->location_type == "home" ? "home" : ($clock->location_type == "site" && $clock->clock_out ? $clock->location->name : null)),
                'Excuse Deducted Today' => $formattedExcuseDeductedToday,
                'Excuse Remaining (Policy 4h)' => $formattedExcuseRemaining,
                'Excuses (Approved)' => $formattedExcuses,
            ]);
        });
    }


    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Department',
            'Date',
            'Clock In',
            'Clock Out',
            'Total Hours (Entry)',
            'Total Hours (Day)',
            'Overtime (Day)',
            'Late Arrive',
            'Early Leave',
            'Location_In',
            'Location_Out',
            'Excuse Deducted Today',
            'Excuse Remaining (Policy 4h)',
            'Excuses (Approved)'
        ];
    }

    /**
     * Define the title for the Excel sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Employee Clock In/Out Data';
    }

    /**
     * Apply styles and formatting to the Excel sheet.
     *
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Apply bold, white font color, and blue background to headers
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'], // White font color
                'size' => 14, // Increase font size for header
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'], // Blue background
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ]);

        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(18); // Code
        $sheet->getColumnDimension('B')->setWidth(22); // Name
        $sheet->getColumnDimension('C')->setWidth(20); // Department
        $sheet->getColumnDimension('D')->setWidth(14); // Date
        $sheet->getColumnDimension('E')->setWidth(14); // Clock_In
        $sheet->getColumnDimension('F')->setWidth(14); // Clock_Out
        $sheet->getColumnDimension('G')->setWidth(16); // Total Hours (Entry)
        $sheet->getColumnDimension('H')->setWidth(18); // Total Hours (Day)
        $sheet->getColumnDimension('I')->setWidth(16); // Overtime (Day)
        $sheet->getColumnDimension('J')->setWidth(14); // Late Arrive
        $sheet->getColumnDimension('K')->setWidth(14); // Early Leave
        $sheet->getColumnDimension('L')->setWidth(22); // Location_In
        $sheet->getColumnDimension('M')->setWidth(22); // Location_Out
        $sheet->getColumnDimension('N')->setWidth(22); // Excuse Deducted Today
        $sheet->getColumnDimension('O')->setWidth(24); // Excuse Remaining (Policy 4h)
        $sheet->getColumnDimension('P')->setWidth(20); // Excuses (Approved)

        // Set row height for the header row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(40); // Adjust the row height of the header row

        // Apply autofilter to all columns
        $sheet->setAutoFilter('A1:P1');
    }


    /**
     * Apply column formatting for date and time columns.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'D' => 'yyyy-mm-dd', // Date format
            'E' => 'hh:mm AM/PM', // Clock In
            'F' => 'hh:mm AM/PM', // Clock Out
        ];
    }

    // =======================
    // Helpers and precompute
    // =======================

    protected function precomputeDailyAggregates(): void
    {
        if (!$this->clocks || $this->clocks->isEmpty()) {
            return;
        }

        // First pass: accumulate totals and track max late/early per (user|date)
        foreach ($this->clocks as $clock) {
            if (!$clock->clock_in) {
                continue;
            }
            $clockIn = Carbon::parse($clock->clock_in);
            $dateKey = $clockIn->format('Y-m-d');
            $key = $clock->user_id . '|' . $dateKey;

            // Total minutes for the day (sum of all entries)
            if ($clock->clock_out) {
                $clockOut = Carbon::parse($clock->clock_out);
                $diff = $clockIn->diffInMinutes($clockOut);
                $this->dailyTotalMinutes[$key] = ($this->dailyTotalMinutes[$key] ?? 0) + $diff;
            }

            // Max late/early per day
            $late = $this->timeToMinutes($clock->late_arrive ?? '00:00:00');
            $early = $this->timeToMinutes($clock->early_leave ?? '00:00:00');
            $this->dailyMaxLateMinutes[$key] = max($this->dailyMaxLateMinutes[$key] ?? 0, $late);
            $this->dailyMaxEarlyMinutes[$key] = max($this->dailyMaxEarlyMinutes[$key] ?? 0, $early);
        }

        // Second pass: compute overtime per (user|date)
        foreach ($this->dailyTotalMinutes as $key => $minutes) {
            $this->dailyOvertimeMinutes[$key] = $this->computeOvertimeMinutes($minutes);
        }

        // Third pass: apply policy-based excuse deductions across dates with total cap 4h (240 minutes) per user.
        // The policy: an employee has two separate 2-hour excuses not combinable in a single day.
        // Implementation: per day, candidate deduction = min(max(late, early), 120). Across the period, cap at 240.
        // Sort keys by user and date
        $keysByUser = [];
        foreach ($this->dailyTotalMinutes as $key => $_) {
            [$userId, $date] = explode('|', $key);
            $keysByUser[$userId][] = $date;
        }
        foreach ($keysByUser as $userId => $dates) {
            sort($dates); // ascending dates
            $remaining = 240; // minutes
            $this->excuseRemainingByUser[$userId] = $remaining;
            foreach ($dates as $date) {
                $key = $userId . '|' . $date;
                $late = $this->dailyMaxLateMinutes[$key] ?? 0;
                $early = $this->dailyMaxEarlyMinutes[$key] ?? 0;
                $candidate = min(max($late, $early), 120); // up to 2 hours per day, not combining late+early
                $deduct = min($candidate, $remaining);
                $remaining -= $deduct;
                $this->dailyExcuseDeducted[$key] = $deduct;
                $this->dailyExcuseRemaining[$key] = $remaining;
            }
            // Save final remaining for user in case some dates had no clocks
            $this->excuseRemainingByUser[$userId] = $remaining;
        }
    }

    protected function computeOvertimeMinutes(int $dailyWorkedMinutes): int
    {
        // Policy:
        // - No overtime until 9 hours (540 minutes)
        // - Crossing 9 hours grants 60 minutes minimum overtime
        // - Every minute after the first overtime hour (i.e., beyond 10 hours) is added to overtime
        if ($dailyWorkedMinutes <= 540) {
            return 0;
        }
        if ($dailyWorkedMinutes <= 600) {
            return 60;
        }
        // 60 minutes for the first overtime hour + every minute beyond 10h
        return 60 + ($dailyWorkedMinutes - 600);
    }

    protected function timeToMinutes(?string $time): int
    {
        if (!$time) {
            return 0;
        }
        // Expecting format H:i:s
        $parts = explode(':', $time);
        if (count($parts) < 2) {
            return 0;
        }
        $h = (int)($parts[0] ?? 0);
        $m = (int)($parts[1] ?? 0);
        return $h * 60 + $m;
    }
}
