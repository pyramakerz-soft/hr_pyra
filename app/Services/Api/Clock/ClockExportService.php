<?php
namespace App\Services\Api\Clock;

use App\Exports\ClocksExport;

class ClockExportService
{
    public function exportClocks($clocks, $department)
    {
        return (new ClocksExport($department))->download('all_user_clocks.xlsx');
    }
}