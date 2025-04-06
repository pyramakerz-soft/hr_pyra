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
        return $this->clocks->map(function ($clock) {
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

            $totalExcuses = $user->excuses()
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->get()
                ->sum(function ($overtime) {
                    return Carbon::parse($overtime->from)->diffInMinutes(Carbon::parse($overtime->to));
                });

            // Format total minutes into HH:MM
            $formattedExcuses = sprintf('%02d:%02d', intdiv($totalExcuses, 60), $totalExcuses % 60);



            return collect([
                'Code' => $clock->user->code,
                'Name' => $clock->user->name,
                'Department' => $clock->user->department ? $clock->user->department->name : 'N/A',
                'Date' => $clockIn->format('Y-m-d'),
                'Clock_In' => $clockIn->format('h:iA'),  // Formatted as 12-hour time (AM/PM)
                'Clock_Out' => $clockOut ? $clockOut->format('h:iA') : null, // Same format for Clock Out
                'totalHours' =>   $formattedTotalHours,
                'Location_In' =>
                $clock->location_type == "float" ?
                    $clock->address_clock_in  : ($clock->location_type == "home" ? "home" : ($clock->location_type == "site" && $clock->clock_in ? $clock->location->name : null)),
                'Location_Out' =>  $clock->location_type == "float" ?
                    $clock->address_clock_out  : ($clock->location_type == "home" ? "home" : ($clock->location_type == "site" && $clock->clock_out ? $clock->location->name : null)),
                    'Excuses'=> $formattedExcuses 
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
            'Total Hours',
            'Location_In',
            'Location_Out',
             'Excuses'
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
        $sheet->getStyle('A1:J1')->applyFromArray([
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
        $sheet->getColumnDimension('A')->setWidth(30); // ID
        $sheet->getColumnDimension('B')->setWidth(30); // Name
        $sheet->getColumnDimension('C')->setWidth(30); // Department
        $sheet->getColumnDimension('D')->setWidth(30); // Date
        $sheet->getColumnDimension('E')->setWidth(30); // Clock_In
        $sheet->getColumnDimension('F')->setWidth(30); // Clock_Out
        $sheet->getColumnDimension('G')->setWidth(30); // total hours

        $sheet->getColumnDimension('H')->setWidth(30); // Location_In
        $sheet->getColumnDimension('I')->setWidth(30); // Location_Out

        $sheet->getColumnDimension('J')->setWidth(30); // Excuses

        // Set row height for the header row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(40); // Adjust the row height of the header row

        // Apply autofilter to all columns
        $sheet->setAutoFilter('A1:J1');
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
}
