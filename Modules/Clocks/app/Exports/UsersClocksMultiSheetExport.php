<?php

namespace Modules\Clocks\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Users\Models\User;

class UsersClocksMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    protected $users;
    protected $startDate;
    protected $endDate;

    public function __construct($users, $startDate = null, $endDate = null)
    {
        if ($users instanceof User) {
            $this->users = collect([$users]);
        } elseif ($users instanceof \Illuminate\Support\Collection || $users instanceof \Illuminate\Database\Eloquent\Collection) {
            $this->users = $users;
        } else {
            $this->users = collect(is_array($users) ? $users : [$users]);
        }
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->users as $user) {
            $sheets[] = new UserClocksSheet($user, $this->startDate, $this->endDate);
        }
        return $sheets;
    }
}





