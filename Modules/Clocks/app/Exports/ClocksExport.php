<?php

namespace Modules\Clocks\Exports;

use App\Models\ClockInOut;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClocksExport implements WithMultipleSheets
{
    use Exportable;

    protected $department;
    protected $userId;
    protected $clocks;

    public function __construct($clocks, $department = null, $userId = null)
    {      
        $this->clocks = $clocks;
        $this->department = $department;
        $this->userId = $userId;
    }

    public function sheets(): array
    {
        $sheets = [];

        $clocksCollection = $this->clocks;

        Log::info('Clocks: ', $clocksCollection->toArray());

        if ($clocksCollection->isEmpty()) {
            Log::error('No clocks found for export.');
            abort(400, 'No clocks found for export.'); 
        }

        $userClocks = $clocksCollection->groupBy('user_id');


        foreach ($userClocks as $userId => $clocks) {
            $user = $clocks->first()->user; 
            $userName = $user->name ?? 'Unknown';  

            $sheets[] = new UserClocksExportById($clocks, $userName);
        }

        if (count($sheets) === 0) {
            Log::warning('No sheets were created because user clocks were empty or not properly grouped.');
            abort(400, 'No sheets were created because user clocks were empty or not properly grouped.');
        }

        return $sheets;
    }
}
