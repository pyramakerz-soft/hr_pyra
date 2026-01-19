<?php
namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AbsentUsersExport implements WithMultipleSheets
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    protected $startDate;
    protected $endDate;
    protected $users;

    public function __construct($startDate = null, $endDate = null, $users = null)
    {
        // If no dates are passed, use today's date as both start and end
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $this->endDate = $endDate ? Carbon::parse($endDate) : Carbon::today();
        $this->users = $users;
    }

    public function sheets(): array
    {
        $sheets = [];

        $currentDate = $this->startDate->copy();
        while ($currentDate->lte($this->endDate)) {
            $sheets[] = new AbsentUsersPerDaySheet($currentDate, $this->users);
            $currentDate->addDay();
        }

        return $sheets;
    }
}
