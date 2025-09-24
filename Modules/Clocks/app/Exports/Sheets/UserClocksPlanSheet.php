<?php

namespace Modules\Clocks\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserClocksPlanSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected Collection $rows;
    protected array $legend;
    protected array $rowColors = [];

    public function __construct(Collection $rows, array $legend = [])
    {
        $this->rows = $rows;
        $this->legend = $legend;
    }

    public function collection(): Collection
    {
        $this->rowColors = [];

        $mapped = $this->rows->values()->map(function (array $row) {
            $color = strtoupper(ltrim($row['Color'] ?? '', '#'));
            $this->rowColors[] = $color;

            return [
                'Employee' => $row['Employee'] ?? '',
                'Code' => $row['Code'] ?? '',
                'Department' => $row['Department'] ?? '',
                'Date' => $row['Date'] ?? '',
                'Category' => $row['Category'] ?? '',
                'Rule' => $row['Rule'] ?? '',
                'Deduction Minutes' => $row['Deduction Minutes'] ?? 0,
                'Deduction HH:MM' => $row['Deduction HH:MM'] ?? '',
                'Monetary Amount' => $row['Monetary Amount'] ?? null,
                'Notes' => $row['Notes'] ?? '',
                'Source' => $row['Source'] ?? '',
            ];
        });

        if (! empty($this->legend)) {
            $mapped = $mapped->push(['Employee' => '', 'Code' => '', 'Department' => '', 'Date' => '', 'Category' => '', 'Rule' => '', 'Deduction Minutes' => '', 'Deduction HH:MM' => '', 'Monetary Amount' => '', 'Notes' => '', 'Source' => '']);
            $this->rowColors[] = null;

            foreach ($this->legend as $category => $color) {
                $this->rowColors[] = strtoupper(ltrim($color, '#'));
                $mapped = $mapped->push([
                    'Employee' => 'Legend',
                    'Code' => '',
                    'Department' => '',
                    'Date' => '',
                    'Category' => ucfirst(str_replace('_', ' ', $category)),
                    'Rule' => 'Color reference',
                    'Deduction Minutes' => '',
                    'Deduction HH:MM' => '',
                    'Monetary Amount' => '',
                    'Notes' => '',
                    'Source' => '',
                ]);
            }
        }

        return $mapped;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Code',
            'Department',
            'Date',
            'Category',
            'Rule',
            'Deduction Minutes',
            'Deduction HH:MM',
            'Monetary Amount',
            'Notes',
            'Source',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
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

        foreach ($this->rowColors as $index => $color) {
            if (! $color) {
                continue;
            }

            $rowNumber = $index + 2; // data starts at row 2
            $sheet->getStyle('A' . $rowNumber . ':K' . $rowNumber)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
            ]);
        }
    }

    public function title(): string
    {
        return 'Plan Details';
    }
}
