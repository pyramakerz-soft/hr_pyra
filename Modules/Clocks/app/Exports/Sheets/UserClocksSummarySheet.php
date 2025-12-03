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
    protected array $rowComparisons;
    protected array $columnFillColors;
    protected string $workedHoursWarningColor;

    public function __construct(Collection $rows, array $sheetLinks = [], array $rowComparisons = [], array $columnFillColors = [])
    {
        $this->rows = $rows;
        $this->sheetLinks = $sheetLinks;
        $this->rowComparisons = $rowComparisons;
        $this->columnFillColors = $columnFillColors ?: [
            'E' => 'D4E9F7',
            'F' => 'CFEAD6',
            'G' => 'F6E6FF',
            'H' => 'FFE3E3',
            'I' => 'FFF1D6',
            'J' => 'D6F5E1',
            'K' => 'F6ECD6',
            'L' => 'EAE0F6',
            'M' => 'FFD8E1',
            'N' => 'F9EDDC',
            'O' => 'D9EFFA',
            'P' => 'E8DAF6',
            'Q' => 'F2F2DC',
            'R' => 'D7EFE3',
            'S' => 'E6F0FA',
            'T' => 'FDEBD2',
            'U' => 'E3F6F5',
            'V' => 'F7E3F0',
            'W' => 'DDEAF2',
            'X' => 'F4E2D7',
            'Y' => 'E6E6FA',
        ];
        $this->workedHoursWarningColor = 'F5B7B1';
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
            'Total Approved OT Hours',
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
            foreach ($this->columnFillColors as $column => $color) {
                $sheet->getStyle($column . '2:' . $column . $lastRow)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => strtoupper($color)],
                    ],
                ]);
            }
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

                    if (!$targetSheet) {
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

                foreach ($this->rowComparisons as $index => $comparison) {
                    $rowNumber = $index + 2;
                    $requiredMinutes = (int) ($comparison['required_minutes'] ?? 0);
                    $workedMinutes = (int) ($comparison['worked_minutes'] ?? 0);

                    if ($requiredMinutes > 0 && $workedMinutes < $requiredMinutes) {
                        $sheet->getStyle('E' . $rowNumber)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $this->workedHoursWarningColor],
                            ],
                        ]);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
