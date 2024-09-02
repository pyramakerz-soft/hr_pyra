<?php

namespace App\Exports;

use App\Models\ClockInOut;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClocksExport implements WithMultipleSheets
{
    use Exportable;
    public function sheets(): array
    {
        $sheets = [];
        $users = ClockInOut::all()->toArray();
        foreach ($users as $user) {
            $sheets[] = new UserClocksExportById($user);
        }
        return $sheets;
    }
}
