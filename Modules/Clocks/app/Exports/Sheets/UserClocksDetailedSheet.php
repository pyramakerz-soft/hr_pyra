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

class UserClocksDetailedSheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle
{
    protected Collection $rows;
    protected array $rowStyles;

    public function __construct(Collection $rows, array $rowStyles)
    {
        $this->rows = $rows;
        $this->rowStyles = $rowStyles;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Clock In',
            'Clock Out',
            'Code',
            'Department',
            'Total Hours in That Day',
            'Total Over time in That Day',
            'Excuse Deducted in That Day',
            'Excuse Remaining (Policy 4h)',
            'Total Excuses in That Day',
            'Is this date has vacation',
            'Location In',
            'Location Out',
            'Attendance Over time in That Day',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:O1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setWidth(30);
        }

        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->setAutoFilter('A1:O1');

        $otStatusColors = [
            'pending' => 'FFF2CC',
            'declined' => 'FFC7CE',
            'approved' => 'F4B183',
        ];
        $weekendColor = 'BDD7EE';
        $multiColor = 'C6E0B4';

        foreach ($this->rowStyles as $mark) {
            $rowNumber = $mark['row'] ?? null;
            if (! $rowNumber) {
                continue;
            }

            if (! empty($mark['ot_status'])) {
                $statusKey = strtolower($mark['ot_status']);
                if (isset($otStatusColors[$statusKey])) {
                    foreach (['H', 'O'] as $col) {
                        $sheet->getStyle($col . $rowNumber)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $otStatusColors[$statusKey]],
                            ],
                        ]);
                    }
                }
            }

            if (! empty($mark['weekend'])) {
                $sheet->getStyle('A' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $weekendColor],
                    ],
                ]);
            }

            if (! empty($mark['multi_segment'])) {
                foreach (['C', 'D'] as $col) {
                    $sheet->getStyle($col . $rowNumber)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $multiColor],
                        ],
                    ]);
                }
            }
        }
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Details';
    }
}
