<?php

namespace Modules\Clocks\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class UserClocksAggregatedSheet implements FromCollection, WithHeadings, WithTitle, WithColumnFormatting, ShouldAutoSize
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
            'Date',
            'Name',
            'Clock In',
            'Clock Out',
            'Code',
            'Department',
            'Total Hours in That Day',
            'Total Over time in That Day',
            'Plan Deduction in That Day',
            'Deduction Details',
            'Excuse Deducted in That Day',
            'Excuse Remaining (Policy 4h)',
            'Total Excuses in That Day',
            'Is this date has vacation',
            'Location In',
            'Location Out',
            'Attendance Over time in That Day',
            'Plan Monetary Amount',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'R' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function title(): string
    {
        return 'Aggregated Details';
    }
}
