<?php

namespace Modules\Clocks\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Clocks\Exports\Sheets\UserClocksDetailedSheet;
use Modules\Clocks\Exports\Sheets\UserClocksSummarySheet;
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

            $userExport = new UserClocksExport($user, $this->startDate, $this->endDate);
            $title = trim(sprintf('%s - %s', $user->code ?? '', $user->name));
            $sheets[] = new UserClocksDetailedSheet(
                $userExport->getDetailedRows(),
                $userExport->getRowStyles(),
                $title !== '' ? $title : 'Details'
            );
        }

        return $sheets;
    }
}
