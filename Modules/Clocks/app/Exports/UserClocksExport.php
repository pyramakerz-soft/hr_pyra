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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UserClocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use Exportable;
    protected $user;
    protected $startDate;
    protected $endDate;

    public function __construct(User $user, $startDate = null, $endDate = null)
    {
        $this->user = $user;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $emptyFeild = 'N/A';

        $now = Carbon::now();
        $defaultStartDate = $now->copy()->subMonth()->day(26)->startOfDay();
        $defaultEndDate = $now->copy()->day(26)->endOfDay();

        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : $defaultStartDate;
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : $defaultEndDate;

        $clocks = $this->user->user_clocks()
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->orderBy('clock_in')
            ->get();

        // Group clocks by date
        $grouped = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->format('Y-m-d');
        });




        // Initialize accumulated totals
        $accumulatedTotalMinutes = 0;
        $accumulatedExcuses = 0;
        $accumulatedOverTimes = 0;
        $accumulatedVacation = 0;



        $finalCollection = new Collection();

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

                        $accumulatedTotalMinutes +=  $diff ;
                        $formattedTotal +=$diff;
                    }

                    $finalCollection->push([
                        'Date' => $formattedDate,
                        'Total Hours in That Day' =>  $emptyFeild,
                        'Total Excuses in That Day' =>  $emptyFeild,
                        'Total Over time in That Day' =>  $emptyFeild,
                        'Is this date has vacation' =>  $emptyFeild,

                        'Name' => $clock->user->name,
                        'Clock In' => $clockIn ? $clockIn->format('h:iA') : '',
                        'Clock Out' => $clockOut ? $clockOut->format('h:iA') : '',
                        'Code' => $clock->user->code,
                        'Department' => $clock->user->department ? $clock->user->department->name : 'N/A',
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

                    'Name' =>  $emptyFeild,
                    'Clock In' =>  $emptyFeild,
                    'Clock Out' =>  $emptyFeild,
                    'Code' =>  $emptyFeild,
                    'Department' =>  $emptyFeild,
                    'Location In' =>  $emptyFeild,
                    'Location Out' =>  $emptyFeild,
                ]);
            }

            // Daily Excuses
            $dailyExcuses = $this->user->excuses()->where('status', 'approved')
                ->whereDate('date', $formattedDate)
                ->get()
                ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));

            $formattedExcuses = sprintf('%02d:%02d', floor($dailyExcuses / 60), $dailyExcuses % 60);


            $accumulatedExcuses += $dailyExcuses;


            // Daily OverTime
            $dailyOverTime = $this->user->overTimes()->where('status', 'approved')
                ->whereDate('to', $formattedDate)
                ->get()
                ->sum(fn($excuse) => Carbon::parse($excuse->from)->diffInMinutes(Carbon::parse($excuse->to)));

            $formattedOverTimes = sprintf('%02d:%02d', floor($dailyOverTime / 60), $dailyOverTime % 60);
            $accumulatedOverTimes += $dailyOverTime;

            // Check vacation
            $isVacation = $this->user->user_vacations()->where('status', 'approved')
                ->whereDate('from_date', '<=', $formattedDate)
                ->whereDate('to_date', '>=', $formattedDate)
                ->exists() ? 1 : 0;
            $accumulatedVacation  += $isVacation;


            $totalHoursInSpecificDay = sprintf('%02d:%02d', floor($formattedTotal / 60), $formattedTotal % 60);

            // Add summary
            $finalCollection->push([
                'Date' => 'SUMMARY---->' . $formattedDate,
                'Total Hours in That Day' =>  $totalHoursInSpecificDay  ,
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



        // Add the summary row for accumulated totals
        $finalCollection->push([
            'Date' => '---TOTAL----',
            'Total Hours in That Day' => sprintf('%02d:%02d', floor($accumulatedTotalMinutes / 60), $accumulatedTotalMinutes % 60),
            'Total Excuses in That Day' => sprintf('%02d:%02d', floor($accumulatedExcuses / 60), $accumulatedExcuses % 60),
            'Total Over time in That Day' => sprintf('%02d:%02d', floor($accumulatedOverTimes / 60), $accumulatedOverTimes % 60),
            'Is this date has vacation' =>  $accumulatedVacation,
            'Name' => 'N/A',
            'Clock In' => $emptyFeild,
            'Clock Out' => $emptyFeild,
            'Code' => $emptyFeild,
            'Department' => $emptyFeild,
            'Location In' => $emptyFeild,
            'Location Out' => $emptyFeild,
        ]);


        return $finalCollection;
    }



    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
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
        $sheet->getStyle('A1:L1')->applyFromArray([
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
        $sheet->getColumnDimension('A')->setWidth(30); // Date
        $sheet->getColumnDimension('B')->setWidth(30); // Name
        $sheet->getColumnDimension('C')->setWidth(30); // Total Excuses
        $sheet->getColumnDimension('D')->setWidth(30); // Total Hours
        $sheet->getColumnDimension('E')->setWidth(30); // Clock In
        $sheet->getColumnDimension('F')->setWidth(30); // Clock Out
        $sheet->getColumnDimension('G')->setWidth(30); // Code
        $sheet->getColumnDimension('H')->setWidth(30); // Department
        $sheet->getColumnDimension('I')->setWidth(30); // Location In
        $sheet->getColumnDimension('J')->setWidth(30); // Location Out
        $sheet->getColumnDimension('K')->setWidth(30); // Location Out
        $sheet->getColumnDimension('L')->setWidth(30); // Location Out

        // Set row height for the header row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(40);

        // Apply autofilter to all columns
        $sheet->setAutoFilter('A1:L1');

        // Highlight summary rows (those containing 'SUMMARY---->')
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $dateValue = $sheet->getCell("A{$row}")->getValue(); // Note: 'Date' is in column A

            if (strpos($dateValue, 'SUMMARY---->') !== false) {
                $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'], // Light green for summary
                    ],
                    'font' => [
                        'color' => ['rgb' => '006100'], // Dark green font
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);
            }

            // Apply red color to the '---TOTAL----' row
            if (strpos($dateValue, '---TOTAL----') !== false) {
                $sheet->getStyle("A{$row}:L{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '008000'], // Red background
                    ],
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'], // White font color
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ]
                ]);
            }
        }
    }


    /**
     * Apply column formatting for date and time columns.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [];
    }
}
