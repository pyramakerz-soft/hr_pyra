<?php

namespace Modules\Clocks\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksSummarySheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle, ShouldAutoSize
{
    protected Collection $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Code',
            'Department',
            'Total Worked Hours',
            'Total OT Hours',
            'Total Attendance OT Hours',
            'Raw Deduction Hours',
            'Excuse Hours Used',
            'Chargeable Deduction Hours',
            'Issue Days',
            'Vacation Days',
            'Base Salary',
            'Hourly Rate',
            'Worked Pay',
            'OT Rate',
            'OT Pay',
            'Gross Pay',
            'Deduction Amount',
            'Plan Deduction Amount',
            'Net Pay',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4BACC6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow >= 2) {
            $sheet->getStyle('E2:F' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
            ]);
            $sheet->getStyle('G2:G' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC7CE'],
                ],
            ]);
            $sheet->getStyle('I2:I' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC7CE'],
                ],
            ]);
            $sheet->getStyle('J2:J' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FCE4D6'],
                ],
            ]);
            $sheet->getStyle('K2:K' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDD7EE'],
                ],
            ]);
        }

        foreach ($this->rows as $index => $row) {
            if (($row['Employee'] ?? null) === 'TOTAL') {
                $rowNumber = $index + 2;
                $sheet->getStyle('A' . $rowNumber . ':U' . $rowNumber)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA'],
                    ],
                ]);
            }
        }
    }

    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER_00,
            'L' => NumberFormat::FORMAT_NUMBER_00,
            'M' => NumberFormat::FORMAT_NUMBER_00,
            'N' => NumberFormat::FORMAT_NUMBER,
            'O' => NumberFormat::FORMAT_NUMBER_00,
            'P' => NumberFormat::FORMAT_NUMBER_00,
            'Q' => NumberFormat::FORMAT_NUMBER_00,
            'R' => NumberFormat::FORMAT_NUMBER_00,
            'S' => NumberFormat::FORMAT_NUMBER_00,
            'T' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
