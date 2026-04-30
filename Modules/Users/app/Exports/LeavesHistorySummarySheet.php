<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeavesHistorySummarySheet implements FromCollection, WithHeadings, WithStyles, WithMapping, WithTitle, WithEvents
{
    protected $userIds;
    protected $years;

    /** Track row => [userId, year] so we can add hyperlinks after sheet is written */
    protected array $rowMeta = [];

    public function __construct($userIds = null, $years = [])
    {
        $this->userIds = $userIds;
        $this->years   = $years;
    }

    public function collection()
    {
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

        $annualType   = VacationType::where('name', 'like', '%Annual leave%')->first();
        $annualTypeId = $annualType ? $annualType->id : null;

        $userIdsInResult = $data->pluck('user_id')->unique()->toArray();
        $yearsInResult   = $data->pluck('year')->unique()->toArray();

        $balances = [];
        if ($annualTypeId && !empty($userIdsInResult) && !empty($yearsInResult)) {
            $balances = UserVacationBalance::whereIn('user_id', $userIdsInResult)
                ->whereIn('year', $yearsInResult)
                ->where('vacation_type_id', $annualTypeId)
                ->get()
                ->keyBy(fn($item) => $item->user_id . '-' . $item->year);
        }

        $grouped = [];
        foreach ($data as $row) {
            $key = $row->user_id . '-' . $row->year;
            if (!isset($grouped[$key])) {
                $balanceRec = $balances[$key] ?? null;
                $allocated  = $balanceRec ? $balanceRec->allocated_days : 0;

                $grouped[$key] = [
                    'user'             => $row->user,
                    'user_id'          => $row->user_id,
                    'year'             => $row->year,
                    'annual_allocated' => $allocated,
                    'annual'           => 0,
                    'sick'             => 0,
                    'casual'           => 0,
                    'emergency'        => 0,
                    'unpaid'           => 0,
                    'other'            => 0,
                    'total_taken'      => 0,
                ];
            }

            $typeName = $row->vacationType ? strtolower($row->vacationType->name) : 'other';
            $days     = $row->total_days;
            $grouped[$key]['total_taken'] += $days;

            if (str_contains($typeName, 'sick')) {
                $grouped[$key]['sick'] += $days;
            } elseif (str_contains($typeName, 'casual')) {
                $grouped[$key]['casual'] += $days;
                $grouped[$key]['annual'] += $days;
            } elseif (str_contains($typeName, 'emergency')) {
                $grouped[$key]['emergency'] += $days;
                $grouped[$key]['annual']    += $days;
            } elseif (str_contains($typeName, 'unpaid') || str_contains($typeName, 'deduction')) {
                $grouped[$key]['unpaid'] += $days;
            } else {
                $grouped[$key]['other'] += $days;
            }
        }

        $rows = array_values($grouped);

        // Store row metadata (data row 2 = index 0, row 3 = index 1 …)
        foreach ($rows as $i => $row) {
            $this->rowMeta[$i + 2] = [
                'user_id' => $row['user_id'],
                'year'    => $row['year'],
            ];
        }

        return collect($rows);
    }

    public function map($row): array
    {
        return [
            $row['user'] ? $row['user']->code : '',
            $row['user'] ? $row['user']->name : '',
            $row['user'] && $row['user']->department ? $row['user']->department->name : '',
            $row['year'],
            $row['annual_allocated'],
            $row['annual'],                                    // Column F – will get hyperlink
            $row['annual_allocated'] - $row['annual'],
            $row['sick'],
            $row['casual'],
            $row['emergency'],
            $row['unpaid'],
            $row['other'],
            $row['total_taken'],
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
            'Annual Used ↗',      // hint that it's clickable
            'Annual Remaining',
            'Sick Leaves',
            'Casual Leaves',
            'Emergency Leaves',
            'Unpaid Leaves',
            'Other Leaves',
            'Total All Leaves',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header row style
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF17253E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Column F header highlight to signal interactivity
        $sheet->getStyle('F1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFF7519']],
        ]);

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->rowMeta as $excelRow => $meta) {
                    $year        = $meta['year'];
                    $sheetName   = "Year {$year}";
                    $cell        = $sheet->getCell("F{$excelRow}");

                    // Internal hyperlink to the Year sheet (# prefix = same-workbook navigation)
                    $cell->setHyperlink(
                        new Hyperlink("#'{$sheetName}'!A1", "View breakdown for {$year}")
                    );

                    // Style the cell to look like a hyperlink
                    $sheet->getStyle("F{$excelRow}")->applyFromArray([
                        'font' => [
                            'color'     => ['argb' => 'FF0563C1'],
                            'underline' => \PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE,
                            'bold'      => true,
                        ],
                    ]);
                }
            },
        ];
    }

    public function title(): string
    {
        return 'Annual Summary';
    }
}
