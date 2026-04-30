<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class LeavesHistoryYearSheet implements FromCollection, WithHeadings, WithStyles, WithMapping, WithTitle, WithEvents
{
    protected $year;
    protected $userIds;

    public function __construct($year, $userIds = null)
    {
        $this->year    = $year;
        $this->userIds = $userIds;
    }

    public function collection()
    {
        $query = UserVacation::with(['user', 'vacationType', 'user.department'])
            ->whereYear('from_date', $this->year)
            ->whereIn('status', ['approved', 'Approved'])
            ->orderBy('user_id')
            ->orderBy('from_date', 'asc');

        if ($this->userIds && is_array($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        } elseif ($this->userIds) {
            $query->where('user_id', $this->userIds);
        }

        return $query->get();
    }

    public function map($vacation): array
    {
        return [
            $vacation->user ? $vacation->user->code : '',
            $vacation->user ? $vacation->user->name : '',
            $vacation->user && $vacation->user->department ? $vacation->user->department->name : '',
            $vacation->vacationType ? $vacation->vacationType->name : '',
            $vacation->from_date ? Carbon::parse($vacation->from_date)->format('Y-m-d') : '',
            $vacation->to_date   ? Carbon::parse($vacation->to_date)->format('Y-m-d')   : '',
            $vacation->days_count,
            $vacation->status ? (is_string($vacation->status) ? $vacation->status : $vacation->status->value) : '',
            $vacation->note ?? '',
            $vacation->created_at ? Carbon::parse($vacation->created_at)->format('Y-m-d H:i') : '',
        ];
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Department',
            'Leave Type',
            'From Date',
            'To Date',
            'Days Count',
            'Status',
            'Note',
            'Created At',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF17253E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Enable auto-filter so HR can filter by Name/Type etc.
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 1) {
                    $sheet->setAutoFilter("A1:J{$highestRow}");
                }

                // Freeze the header row
                $sheet->freezePane('A2');

                // Alternate row shading for readability
                for ($row = 2; $row <= $highestRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFF0F4FF']],
                        ]);
                    }
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Year ' . $this->year;
    }
}
