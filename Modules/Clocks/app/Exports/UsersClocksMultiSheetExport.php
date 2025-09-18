<?php

namespace Modules\Clocks\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Clocks\Exports\Sheets\UserClocksSummarySheet;
use Modules\Clocks\Exports\UserClocksExport;
use Modules\Users\Models\User;

class UsersClocksMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected Collection $users;
    protected $startDate;
    protected $endDate;

    public function __construct($users, $startDate = null, $endDate = null)
    {
        if ($users instanceof User) {
            $this->users = collect([$users]);
        } elseif ($users instanceof Collection || $users instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->users = collect($users);
        } else {
            $this->users = collect(is_array($users) ? $users : [$users]);
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        $sheets = [];

        $summaryExport = new UserClocksExport($this->users, $this->startDate, $this->endDate);
        $sheets[] = new UserClocksSummarySheet($summaryExport->getSummaryRows());

        foreach ($this->users as $user) {
            if (! $user instanceof User) {
                continue;
            }

            $sheets[] = new UserClocksSheet($user, $this->startDate, $this->endDate);
        }

        return $sheets;
    }
}





