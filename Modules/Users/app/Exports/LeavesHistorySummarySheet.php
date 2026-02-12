<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeavesHistorySummarySheet implements FromCollection, WithHeadings, WithStyles, WithMapping, WithTitle
{
    protected $userIds;
    protected $years;

    public function __construct($userIds = null, $years = [])
    {
        $this->userIds = $userIds;
        $this->years = $years;
    }

    public function collection()
    {
        // 1. Get Leave History Data
        $query = UserVacation::with(['user', 'vacationType', 'user.department'])
            ->select(
                'user_id',
                DB::raw('YEAR(from_date) as year'),
                'vacation_type_id',
                DB::raw('SUM(days_count) as total_days')
            )
            ->groupBy('user_id', 'year', 'vacation_type_id')
            ->orderBy('year', 'desc')
            ->orderBy('user_id');

        if ($this->userIds && is_array($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        } elseif ($this->userIds) {
            $query->where('user_id', $this->userIds);
        }

        if (!empty($this->years)) {
            $query->whereIn(DB::raw('YEAR(from_date)'), $this->years);
        }

        $data = $query->get();

        // 2. Get Annual Vacation Type ID
        $annualType = VacationType::where('name', 'like', '%Annual leave%')->first();
        $annualTypeId = $annualType ? $annualType->id : null;
        // 3. Get Balances for these users/years
        $userIdsInResult = $data->pluck('user_id')->unique()->toArray();
        $yearsInResult = $data->pluck('year')->unique()->toArray();

        $balances = [];
        if ($annualTypeId && !empty($userIdsInResult) && !empty($yearsInResult)) {
            $balances = UserVacationBalance::whereIn('user_id', $userIdsInResult)
                ->whereIn('year', $yearsInResult)
                ->where('vacation_type_id', $annualTypeId)
                ->get()
                ->keyBy(function ($item) {
                    return $item->user_id . '-' . $item->year;
                });
        }

        // 4. Transform and Merge
        $grouped = [];
        foreach ($data as $row) {
            $key = $row->user_id . '-' . $row->year;
            if (!isset($grouped[$key])) {
                $balanceRec = isset($balances[$key]) ? $balances[$key] : null;
                $allocated = $balanceRec ? $balanceRec->allocated_days : 0;
                // We can use the used_days from DB or our calculated. 
                // Let's use our calculated 'Annual' sum for consistency with the columns, 
                // but for "Balance" calculation, maybe allocated is enough?
                // User asked for "balance" and "used days".
                // I'll show Allocated (Base Balance) and Calculated Used.

                $grouped[$key] = [
                    'user' => $row->user,
                    'year' => $row->year,
                    'annual_allocated' => $allocated,
                    'annual' => 0,
                    'sick' => 0,
                    'casual' => 0,
                    'emergency' => 0,
                    'unpaid' => 0,
                    'other' => 0,
                    'total_taken' => 0
                ];
            }

            $typeName = $row->vacationType ? strtolower($row->vacationType->name) : 'other';
            $days = $row->total_days;
            $grouped[$key]['total_taken'] += $days;

            if (str_contains($typeName, 'sick')) {
                $grouped[$key]['sick'] += $days;
            } elseif (str_contains($typeName, 'casual')) {
                $grouped[$key]['casual'] += $days;
                $grouped[$key]['annual'] += $days;
            } elseif (str_contains($typeName, 'emergency')) {
                $grouped[$key]['emergency'] += $days;
                $grouped[$key]['annual'] += $days;
            } elseif (str_contains($typeName, 'unpaid') || str_contains($typeName, 'deduction')) {
                $grouped[$key]['unpaid'] += $days;
            } else {
                $grouped[$key]['other'] += $days;
            }
        }

        return collect(array_values($grouped));
    }

    public function map($row): array
    {
        return [
            $row['user'] ? $row['user']->code : '',
            $row['user'] ? $row['user']->name : '',
            $row['user'] && $row['user']->department ? $row['user']->department->name : '',
            $row['year'],
            $row['annual_allocated'],   // Balance (Allocated)
            $row['annual'],             // Used (Annual)
            $row['annual_allocated'] - $row['annual'], // Remaining (Calculated)
            $row['sick'],
            $row['casual'],
            $row['emergency'],
            $row['unpaid'],
            $row['other'],
            $row['total_taken']
        ];
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Department',
            'Year',
            'Annual Allocated',
            'Annual Used',
            'Annual Remaining',
            'Sick Leaves',
            'Casual Leaves',
            'Emergency Leaves',
            'Unpaid Leaves',
            'Other Leaves',
            'Total All Leaves'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function title(): string
    {
        return "Annual Summary";
    }
}
