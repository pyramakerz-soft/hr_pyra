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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected $users;
    protected $startDate;
    protected $endDate;

    public function __construct($users, $startDate = null, $endDate = null)
    {
        $this->users = $users;
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

            $grouped = $clocks->groupBy(function ($clock) {
                return Carbon::parse($clock->clock_in)->format('Y-m-d');
            });

            // Initialize accumulated totals
            $accumulatedTotalMinutes = 0;
            $accumulatedExcuses = 0;
            $accumulatedOverTimes = 0;
            $accumulatedVacation = 0;

            // Loop through all dates from start to end
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $formattedDate = $date->format('Y-m-d');
                $dailyClocks = $grouped->get($formattedDate, collect());
                $formattedTotal = null;
                if ($dailyClocks->isNotEmpty()) {
                    foreach ($dailyClocks as $clock) {
                        $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                        $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;

                        if ($clockIn && $clockOut) {
                            $diff = $clockIn->diffInMinutes($clockOut);
                            $accumulatedTotalMinutes +=  $diff;
                            $formattedTotal += $diff;
                        }
                        $clockInFormatted = $clockIn ? $clockIn->addHours($timezoneValue)->format('h:i A') : '';
                        $clockOutFormatted = $clockOut ? $clockOut->addHours($timezoneValue)->format('h:i A') : '';
                        $finalCollection->push([
                            'Date' => $formattedDate,
                            'Total Hours in That Day' =>  $emptyFeild,
                            'Total Excuses in That Day' =>  $emptyFeild,
                            'Total Over time in That Day' =>  $emptyFeild,
                            'Is this date has vacation' =>  $emptyFeild,
                            'Name' => $user->name,
                            'Clock In' => $clockInFormatted,
                            'Clock Out' => $clockOutFormatted,
                            'Code' => $user->code,
                            'Department' => $user->department ? $clock->user->department->name : 'N/A',
                            'Location In' => $clock->location_type === "float"
                                ? $clock->address_clock_in
                                : ($clock->location_type === "home"
                                    ? "home"
                                    : ($clock->location_type === "site" && $clock->clock_in
                                        ? optional($clock->location)->name
                                        : null)),
                            'Location Out' => $clock->location_type === "float"
                                ? $clock->address_clock_out
                                : ($clock->location_type === "home"
                                    ? "home"
                                    : ($clock->location_type === "site" && $clock->clock_out
                                        ? optional($clock->location)->name
                                        : null)),
                        ]);
                    }
                } else {
                    // No clocks in this day → add empty row
                    $finalCollection->push([
                        'Date' => $formattedDate,
                        'Total Hours in That Day' =>  $emptyFeild,
                        'Total Excuses in That Day' =>  $emptyFeild,
                        'Total Over time in That Day' =>  $emptyFeild,
                        'Is this date has vacation' =>  $emptyFeild,
                        'Name' => $user->name,
                        'Clock In' =>  $emptyFeild,
                        'Clock Out' =>  $emptyFeild,
                        'Code' => $user->code,
                        'Department' => $user->department?->name ?? $emptyFeild,
                        'Location In' =>  $emptyFeild,
                        'Location Out' =>  $emptyFeild,
                    ]);
                }

                // Daily Excuses
                $dailyExcuses = $user->excuses()->where('status', 'approved')
                    ->whereDate('date', $formattedDate)
                    ->get()
                    ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
                $formattedExcuses = sprintf('%02d:%02d', floor($dailyExcuses / 60), $dailyExcuses % 60);
                $accumulatedExcuses += $dailyExcuses;

                // Daily OverTime
                $dailyOverTime = $user->overTimes()->where('status', 'approved')
                    ->whereDate('to', $formattedDate)
                    ->get()
                    ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));
                $formattedOverTimes = sprintf('%02d:%02d', floor($dailyOverTime / 60), $dailyOverTime % 60);
                $accumulatedOverTimes += $dailyOverTime;

                // Check vacation
                $isVacation = $user->user_vacations()->where('status', 'approved')
                    ->whereDate('from_date', '<=', $formattedDate)
                    ->whereDate('to_date', '>=', $formattedDate)
                    ->exists() ? 1 : 0;
                $accumulatedVacation  += $isVacation;

                $totalHoursInSpecificDay = sprintf('%02d:%02d', floor($formattedTotal / 60), $formattedTotal % 60);

                // Add summary
                $finalCollection->push([
                    'Date' => 'SUMMARY---->' . $formattedDate,
                    'Total Hours in That Day' =>  $totalHoursInSpecificDay,
                    'Total Excuses in That Day' => $formattedExcuses,
                    'Total Over time in That Day' => $formattedOverTimes,
                    'Is this date has vacation' => $isVacation == 0 ? 'NO' : 'YES',
                    'Name' => 'N/A',
                    'Clock In' =>  $emptyFeild,
                    'Clock Out' =>  $emptyFeild,
                    'Code' =>  $emptyFeild,
                    'Department' =>  $emptyFeild,
                    'Location In' =>  $emptyFeild,
                    'Location Out' =>  $emptyFeild,
                ]);

                // Empty row between days
                $finalCollection->push([
                    'Date' => '',
                    'Total Hours in That Day' =>  '',
                    'Total Excuses in That Day' =>   '',
                    'Total Over time in That Day' => '',
                    'Is this date has vacation' =>   '',
                    'Name' =>  '',
                    'Clock In' =>   '',
                    'Clock Out' =>  '',
                    'Code' =>  '',
                    'Department' =>  '',
                    'Location In' =>  '',
                    'Location Out' =>  '',
                ]);
            }

            // Add the summary row for accumulated totals for this user
            $finalCollection->push([
                'Date' => '---TOTAL for '.$user->name.'----',
                'Total Hours in That Day' => sprintf('%02d:%02d', floor($accumulatedTotalMinutes / 60), $accumulatedTotalMinutes % 60),
                'Total Excuses in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuses / 60), $accumulatedExcuses % 60),
                'Total Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedOverTimes / 60), $accumulatedOverTimes % 60),
                'Is this date has vacation' =>  $accumulatedVacation == '0' ? 'NO VACATIONS' : $accumulatedVacation . '  Days ',
                'Name' => $user->name,
                'Clock In' => $emptyFeild,
                'Clock Out' => $emptyFeild,
                'Code' => $user->code,
                'Department' => $user->department?->name ?? $emptyFeild,
                'Location In' => $emptyFeild,
                'Location Out' => $emptyFeild,
            ]);

            // Separator between users
            $finalCollection->push([
                'Date' => '--------------------',
                'Total Hours in That Day' =>  '',
                'Total Excuses in That Day' =>   '',
                'Total Over time in That Day' => '',
                'Is this date has vacation' =>   '',
                'Name' =>  '',
                'Clock In' =>   '',
                'Clock Out' =>  '',
                'Code' =>  '',
                'Department' =>  '',
                'Location In' =>  '',
                'Location Out' =>  '',
            ]);
        }
        return $finalCollection;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Total Hours in That Day',
            'Total Excuses in That Day',
            'Total Over time in That Day',
            'Is this date has vacation',
            'Name',
            'Clock In',
            'Clock Out',
            'Code',
            'Department',
            'Location In',
            'Location Out',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:L1')->applyFromArray([
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
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setWidth(30);
        }
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->setAutoFilter('A1:L1');
    }

    public function columnFormats(): array
    {
        return [];
    }
}
