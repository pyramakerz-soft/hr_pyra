<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UsersExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting
{
    use Exportable;

    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->users->map(function ($user) {
            // Check gender
            $gender = $user->gender == 'M' ? "Male" : ($user->gender == 'F' ? "Female" : "Other");
            
            // Get work types
            $workTypes = $user->work_types->pluck('name')->implode(', ');

            // Get locations
            $locations = $user->user_locations->pluck('name')->implode(', ');

            // Get hiring date (using created_at for simplicity)
            $hiringDate = $user->created_at->format('Y-m-d');

            return [
                'name' => $user->name,
                'gender' => $gender,
                'code' => $user->code,
                'department' => $user->department->name ?? null,
                'position' => $user->user_detail->emp_type ?? null,
                'role' => $user->getRoleName(),
                'email' => $user->email,
                'phone' => $user->phone,
                'working_hours' => $user->user_detail->working_hours_day ?? null,
                'work_type' => $workTypes,
                'location' => $locations,
                'hiring_date' => $hiringDate,
            ];
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
            'Name',
            'Gender',
            'Code',
            'Department',
            'Position',
            'Role',
            'Email',
            'Phone',
            'Working Hours',
            'Work Type', // Add work type heading
            'Location',  // Add location heading
            'Hiring Date', // Add hiring date heading
        ];
    }

    /**
     * Define the title for the Excel sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Employees Data';
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
        $sheet->getColumnDimension('A')->setWidth(30); 
        $sheet->getColumnDimension('B')->setWidth(30); 
        $sheet->getColumnDimension('C')->setWidth(30); 
        $sheet->getColumnDimension('D')->setWidth(30); 
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(30); 
        $sheet->getColumnDimension('G')->setWidth(30); 
        $sheet->getColumnDimension('H')->setWidth(30);
        $sheet->getColumnDimension('I')->setWidth(30); // Work Type column
        $sheet->getColumnDimension('J')->setWidth(30); // Location column
        $sheet->getColumnDimension('K')->setWidth(70); // Hiring Date column
        $sheet->getColumnDimension('L')->setWidth(70); // Hiring Date column

        // Set row height for the header row (Row 1)
        $sheet->getRowDimension(1)->setRowHeight(40); // Adjust the row height of the header row
    
        // Apply autofilter to all columns
        $sheet->setAutoFilter('A1:M1');
    }

    /**
     * Apply column formatting for date and time columns.
     *
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'K' => 'yyyy-mm-dd', // Format for hiring date
        ];
    }
}
