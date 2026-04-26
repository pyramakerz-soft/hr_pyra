<?php

namespace Modules\Users\Exports;

use Modules\Users\Models\SalaryAdjustment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class SalaryAdjustmentExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fromDate;
    protected $toDate;

    public function __construct($fromDate, $toDate)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function query()
    {
        return SalaryAdjustment::with('user')
            ->whereBetween('adjustment_date', [$this->fromDate, $this->toDate])
            ->orderBy('adjustment_date', 'asc');
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee Code',
            'Amount',
            'Reason',
            'Adjustment Date',
            'Created At'
        ];
    }

    public function map($adjustment): array
    {
        return [
            $adjustment->user?->name,
            $adjustment->user?->code,
            $adjustment->amount,
            $adjustment->reason,
            $adjustment->adjustment_date,
            $adjustment->created_at->toDateTimeString(),
        ];
    }
}
