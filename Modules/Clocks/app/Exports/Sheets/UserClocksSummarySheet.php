<?php

namespace Modules\Clocks\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksSummarySheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $rows;
    protected array $sheetLinks;

    public function __construct(Collection $rows, array $sheetLinks = [])
    {
        $this->rows = $rows;
        $this->sheetLinks = $sheetLinks;
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
            'Total Days Worked',
            'Total Worked Hours',
            'Total OT Hours',
            'Total Attendance OT Hours',
            'Raw Deduction Hours',
            'Excuse Hours Used',
            'Approved Excuses',
            'Pending Excuses',
            'Rejected Excuses',
            'Chargeable Deduction Hours',
            'Issue Days',
            'Vacation Days',
            'Vacation Days Left',
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
        $sheet->getStyle('A1:Z1')->applyFromArray([
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
            $sheet->getStyle('E2:G' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C6EFCE'],
                ],
            ]);
            $sheet->getStyle('H2:H' . $lastRow)->applyFromArray([
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
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => ['rgb' => 'E8F5E9'],
                    'endColor' => ['rgb' => 'A5D6A7'],
                ],
            ]);
            $sheet->getStyle('K2:K' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => ['rgb' => 'FFF8D5'],
                    'endColor' => ['rgb' => 'FFD966'],
                ],
            ]);
            $sheet->getStyle('L2:L' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => ['rgb' => 'F8CECC'],
                    'endColor' => ['rgb' => 'EA9999'],
                ],
            ]);
            $sheet->getStyle('M2:M' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC7CE'],
                ],
            ]);
            $sheet->getStyle('N2:N' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FCE4D6'],
                ],
            ]);
            $sheet->getStyle('O2:P' . $lastRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDD7EE'],
                ],
            ]);
        }

        foreach ($this->rows as $index => $row) {
            if (($row['Employee'] ?? null) === 'TOTAL') {
                $rowNumber = $index + 2;
                $sheet->getStyle('A' . $rowNumber . ':Z' . $rowNumber)->applyFromArray([
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
            'D' => NumberFormat::FORMAT_NUMBER,
            'N' => NumberFormat::FORMAT_NUMBER,
            'O' => NumberFormat::FORMAT_NUMBER,
            'P' => NumberFormat::FORMAT_NUMBER_00,
            'Q' => NumberFormat::FORMAT_NUMBER_00,
            'R' => NumberFormat::FORMAT_NUMBER_00,
            'S' => NumberFormat::FORMAT_NUMBER_00,
            'T' => NumberFormat::FORMAT_NUMBER_00,
            'U' => NumberFormat::FORMAT_NUMBER_00,
            'V' => NumberFormat::FORMAT_NUMBER_00,
            'W' => NumberFormat::FORMAT_NUMBER_00,
            'X' => NumberFormat::FORMAT_NUMBER_00,
            'Y' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->rows->values() as $index => $row) {
                    $employee = $row['Employee'] ?? null;
                    $targetSheet = $employee && isset($this->sheetLinks[$employee])
                        ? $this->sheetLinks[$employee]
                        : null;

                    if (! $targetSheet) {
                        continue;
                    }

                    $rowNumber = $index + 2;
                    $cellRef = 'A' . $rowNumber;
                    $quotedSheet = str_replace("'", "''", $targetSheet);

                    $sheet->getCell($cellRef)->getHyperlink()->setUrl("sheet://'" . $quotedSheet . "'!A1");
                    $sheet->getStyle($cellRef)->applyFromArray([
                        'font' => [
                            'color' => ['rgb' => Color::COLOR_BLUE],
                            'underline' => 'single',
                        ],
                    ]);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
