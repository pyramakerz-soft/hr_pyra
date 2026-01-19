<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class LeavesHistoryExport implements FromCollection, WithHeadings, WithStyles, WithMapping
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    protected $userIds;
    protected $fromDate;
    protected $toDate;

    public function __construct($userIds = null, $fromDate = null, $toDate = null)
    {
        $this->userIds = $userIds;
        $this->fromDate = $fromDate; // Optional filter start
        $this->toDate = $toDate;     // Optional filter end
    }

    public function collection()
    {
        $query = UserVacation::with(['user', 'vacationType'])
            ->orderBy('from_date', 'desc');

        if ($this->userIds && is_array($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        } elseif ($this->userIds) { // Single ID
            $query->where('user_id', $this->userIds);
        }

        if ($this->fromDate) {
            $query->whereDate('from_date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('to_date', '<=', $this->toDate);
        }

        // "Approved" status usually implies "Taken", but let's just dump all history or maybe filter by not rejected?
        // User said "all leave days taken", implying actual leaves. 
        // Often 'pending' are not "taken" yet. 'rejected' definitely not.
        // Let's filter for approved for now, or maybe all non-rejected?
        // User request: "display all leave days taken". Usually means approved/completed.
        // I will default to all for history purposes but maybe sorting/status will help.
        // Actually, "History Report" usually implies a record of everything or at least approved.
        // I'll stick to displaying all and include the Status column so they can filter in Excel. 

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
            $vacation->to_date ? Carbon::parse($vacation->to_date)->format('Y-m-d') : '',
            $vacation->days_count,
            $vacation->status ? (is_string($vacation->status) ? $vacation->status : $vacation->status->value) : '', // Handle Enum or string
            $vacation->note,
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
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
