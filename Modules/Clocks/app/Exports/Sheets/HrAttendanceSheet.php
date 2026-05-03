<?php

namespace Modules\Clocks\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class HrAttendanceSheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle, ShouldAutoSize
{
    protected Collection $rows;
    protected array $rowStyles;
    protected string $title;
    
    /**
     * Locations to exclude from "School Visit" counts.
     * Edit this array to add/remove locations.
     */
    protected array $excludedLocations = [
        'pyramakerz',
        'cairo',
        'alexandria zizinia',
        'ciramakerz',
        'office',
        'company'
    ];

    public function __construct(Collection $rows, array $rowStyles, string $title = "Attendance")
    {
        $this->rows = $rows;
        $this->rowStyles = $rowStyles;
        $this->title = $title;
    }

    public function collection(): Collection
    {
        // Select and order columns to match the latest template image + extra fields
        return $this->rows->map(function ($row) {
            return [
                'Date' => $row['Date'] ?? '',
                'Code' => $row['Code'] ?? '',
                'Name' => $row['Name'] ?? '',
                'Date Of Joining' => $row['Hiring Date'] ?? '',
                'Department' => $row['Department'] ?? '',
                'Work Type' => $row['Work Type'] ?? '',
                'Clock In' => $row['Clock In'] ?? '',
                'Clock Out' => $row['Clock Out'] ?? '',
                'Total Hours' => $row['Total Hours in That Day'] ?? '',
                'Over time' => $row['Total Over time in That Day'] ?? '',
                'Late Deductions' => $row['Plan Deduction in That Day'] ?? '',
                'Deduction Days' => $row['Deduction Days'] ?? '',
                'Adjustment' => '', // Blank column for manual input
                'Location In' => $row['Location In'] ?? '',
                'Location Out' => $row['Location Out'] ?? '',
                'Remarks' => $row['Is this date has vacation'] ?? '',
                'Transportation Status' => $row['Transportation Status'] ?? '',
                'School Visits' => $row['School Visits'] ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Code',
            'Name',
            'Date Of Joining',
            'Department',
            'Work Type',
            'Clock In',
            'Clock Out',
            'Total Hours',
            'Over time',
            'Late Deductions',
            'Deduction Days',
            'Adjustment',
            'Location In',
            'Location Out',
            'Remarks',
            'Transportation Status',
            'School Visits',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header Style (Grey Background, White Text)
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
                'size' => 16,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'A6A6A6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Specific Header Colors from Template
        // Adjustment (M) - Peach/Orange
        $sheet->getStyle('M1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8CBAD'],
            ],
            'font' => ['color' => ['rgb' => '000000']],
        ]);

        // Remarks (P) - Yellow
        $sheet->getStyle('P1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFF00'],
            ],
            'font' => ['color' => ['rgb' => '000000']],
        ]);

        // Auto-fit column widths
        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);

        // Center align all text and add black borders
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:R' . $highestRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        foreach ($this->rowStyles as $mark) {
            $rowNumber = $mark['row'] ?? null;
            if (!$rowNumber) {
                continue;
            }

            // Teal Background for Custom Vacations (#00FFCC)
            if (!empty($mark['has_custom_vacation'])) {
                $sheet->getStyle('A' . $rowNumber . ':R' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00FFCC'],
                    ],
                ]);
            }

            // Cyan/Turquoise Background for Weekends (#00FFFF)
            if (!empty($mark['weekend'])) {
                $sheet->getStyle('A' . $rowNumber . ':R' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00FFFF'],
                    ],
                ]);
            }

            // Yellow Background for Clock In/Out on Requested Vacations (#FFFF00)
            if (!empty($mark['has_requested_vacation'])) {
                $sheet->getStyle('G' . $rowNumber . ':H' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                ]);
            }

            // Grey background for Clock In/Out cells if multi-segment
            if (!empty($mark['multi_segment'])) {
                $sheet->getStyle('G' . $rowNumber . ':H' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);
            }

            // Missing Clock Out Color (#FCE4D6)
            $isMissingOut = (isset($mark['issue_columns']) && in_array('D', $mark['issue_columns'], true)) || !empty($mark['is_missing_out']);
            if ($isMissingOut) {
                $sheet->getStyle('H' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FCE4D6'],
                    ],
                ]);
            }

            // Green Background (#00B050) for Late Deductions on Requested Vacations
            if (!empty($mark['has_requested_vacation'])) {
                $sheet->getStyle('K' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '00B050'],
                    ],
                    'font' => ['color' => ['rgb' => 'FFFFFF']], // White font for visibility
                ]);
            }

            // Total Hours (column I) - always black font, no red coloring

            // Late Deductions (column K) - RED font only when there is an actual non-zero deduction
            $rawDeductionMinutes = $mark['raw_deduction_minutes'] ?? null;
            $hasActualDeduction = $rawDeductionMinutes !== null && $rawDeductionMinutes > 0;
            $hasNonZeroExcuse = isset($mark['row_data']['Total Excuses in That Day']) &&
                                $mark['row_data']['Total Excuses in That Day'] !== '00:00' &&
                                $mark['row_data']['Total Excuses in That Day'] !== '';
            if ($hasActualDeduction || $hasNonZeroExcuse) {
                $sheet->getStyle('K' . $rowNumber)->getFont()->setColor(new Color(Color::COLOR_RED));
            }

            // Total Row styling
            $isTotalRow = (isset($mark['row_data']['Date']) && str_contains($mark['row_data']['Date'], 'TOTAL'));
            if ($isTotalRow) {
                $sheet->getStyle('A' . $rowNumber . ':R' . $rowNumber)->getFont()->setBold(true);
                
                // Teal background for A-H and M-R (#129191)
                $tealStyle = [
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '129191'],
                    ],
                ];
                $sheet->getStyle('A' . $rowNumber . ':H' . $rowNumber)->applyFromArray($tealStyle);
                $sheet->getStyle('M' . $rowNumber . ':O' . $rowNumber)->applyFromArray($tealStyle);

                // Yellow background for Total Hours and Overtime (I-J) (#FFFF00)
                $sheet->getStyle('I' . $rowNumber . ':J' . $rowNumber)->applyFromArray([
                    'font' => ['color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'],
                    ],
                ]);

                // Red background for Late Deductions and Deduction Days (K-L) (#FF0000)
                $sheet->getStyle('K' . $rowNumber . ':L' . $rowNumber)->applyFromArray([
                    'font' => ['color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FF0000'],
                    ],
                ]);
            }
            
            // Header/Section row styling
            if (!empty($mark['header_row'])) {
                $sheet->getStyle('A' . $rowNumber . ':R' . $rowNumber)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA'],
                    ],
                ]);
            }
        }
    }




    public function columnFormatting(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->title;
    }

    public function columnFormats(): array
    {
        return [];
    }
}
