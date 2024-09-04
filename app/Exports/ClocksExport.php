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

        $users = ClockInOut::with('user')->select('user_id')->distinct()->get();

        foreach ($users as $user) {
            $clocks = ClockInOut::where('user_id', $user->user_id)->get();

            $userName = $user->user->name;

            $sheets[] = new UserClocksExportById($clocks, $userName);
        }

        return $sheets;
    }
}
