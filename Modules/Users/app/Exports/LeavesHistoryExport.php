<?php

namespace Modules\Users\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class LeavesHistoryExport implements WithMultipleSheets
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    protected $userIds;
    protected $fromDate;
    protected $toDate;

    public function __construct($userIds = null, $fromDate = null, $toDate = null)
    {
        $this->userIds = $userIds;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Determine years
        $query = UserVacation::query();
        if ($this->userIds && is_array($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        } elseif ($this->userIds) {
            $query->where('user_id', $this->userIds);
        }
        if ($this->fromDate) {
            $query->whereDate('from_date', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $query->whereDate('from_date', '<=', $this->toDate);
        }

        // Get distinct years
        $yearsData = $query->selectRaw('YEAR(from_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // If no data found but date range exists, maybe include those years? 
        // Or if no data, just return empty?
        if (empty($yearsData) && $this->fromDate && $this->toDate) {
            $start = Carbon::parse($this->fromDate)->year;
            $end = Carbon::parse($this->toDate)->year;
            for ($y = $end; $y >= $start; $y--) {
                $yearsData[] = $y;
            }
        }

        // Add Summary Sheet
        $sheets[] = new LeavesHistorySummarySheet($this->userIds, $yearsData);

        // Add Year Sheets
        foreach ($yearsData as $year) {
            $sheets[] = new LeavesHistoryYearSheet($year, $this->userIds);
        }

        return $sheets;
    }
}
