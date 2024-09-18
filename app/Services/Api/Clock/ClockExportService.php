<?php
namespace App\Services\Api\Clock;

use App\Exports\ClocksExport;

class ClockExportService
{
    public function exportClocks($clocks, $department, $userId = null)
    {
        return (new ClocksExport($department, $userId))->download('all_user_clocks.xlsx');
    }
}
