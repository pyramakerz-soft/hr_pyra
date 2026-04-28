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

use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UserClocksDetailedSheet implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, WithTitle, ShouldAutoSize
{
    protected Collection $rows;
    protected array $rowStyles;
    protected string $title;
    protected string $workedHoursWarningColor;

    public function __construct(Collection $rows, array $rowStyles, string $title = "Details")
    {
        $this->rows = $rows;
        $this->rowStyles = $rowStyles;
        $this->title = $title;
        $this->workedHoursWarningColor = 'F7BFA0';
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
            'Hiring Date',
            'Work Type',
            'Transportation Status',
            'Total Hours in That Day',
            'Total Over time in That Day',
            'is_mission',
            'OT Direct Approved By',
            'OT Head Approved By',
            'Plan Deduction in That Day',
            'Deduction Details',
            'Deduction Days',
            'Excuse Deducted in That Day',
            'Excuse Remaining (Policy 4h)',
            'Total Excuses in That Day',
            'Is this date has vacation',
            'Vacation Direct Approved By',
            'Vacation Head Approved By',
            'Location In',
            'Location Out',
            'Attendance Over time in That Day',
            'School Visits',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:AA1')->applyFromArray([
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

        foreach (range('A', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('AA')->setAutoSize(true);

        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->setAutoFilter('A1:AA1');

        $otStatusColors = [
            'pending' => 'FFF2CC',
            'declined' => 'FFC7CE',
            'approved' => 'F4B183',
        ];
        $weekendColor = 'BDD7EE';
        $multiColor = 'C6E0B4';

        foreach ($this->rowStyles as $mark) {
            $rowNumber = $mark['row'] ?? null;
            if (!$rowNumber) {
                continue;
            }

            if (!empty($mark['ot_status'])) {
                $statusKey = strtolower($mark['ot_status']);
                if (isset($otStatusColors[$statusKey])) {
                    foreach (['K', 'Z'] as $col) {
                        $sheet->getStyle($col . $rowNumber)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $otStatusColors[$statusKey]],
                            ],
                        ]);
                    }
                }
            }

            if (!empty($mark['weekend'])) {
                $sheet->getStyle('A' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $weekendColor],
                    ],
                ]);
            }

            if (!empty($mark['multi_segment'])) {
                foreach (['C', 'D'] as $col) {
                    $sheet->getStyle($col . $rowNumber)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $multiColor],
                        ],
                    ]);
                }
            }

            if (!empty($mark['deduction_color'])) {
                $colorHex = strtoupper(ltrim($mark['deduction_color'], '#'));
                foreach (['O', 'P'] as $col) {
                    $sheet->getStyle($col . $rowNumber)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $colorHex],
                        ],
                    ]);
                }
            }

            if (!empty($mark['vacation'])) {
                $sheet->getStyle('U' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'BDD7EE'],
                    ],
                ]);
            }

            if (!empty($mark['header_row'])) {
                $sheet->getStyle('A' . $rowNumber . ':AA' . $rowNumber)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2EFDA'],
                    ],
                ]);
            }

            $requiredMinutes = isset($mark['required_minutes']) ? (int) $mark['required_minutes'] : 0;
            $workedMinutes = isset($mark['worked_minutes']) ? (int) $mark['worked_minutes'] : 0;
            if ($requiredMinutes > 0 && $workedMinutes < $requiredMinutes) {
                $sheet->getStyle('J' . $rowNumber)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $this->workedHoursWarningColor],
                    ],
                ]);
            }
        }
    }

    public function columnFormats(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->title;
    }
}
