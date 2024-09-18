<?php

namespace App\Exports;

use App\Models\ClockInOut;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClocksExport implements WithMultipleSheets
{
    use Exportable;

    protected $department;
    protected $userId;

    public function __construct($department = null, $userId = null)
    {
        $this->department = $department;
        $this->userId = $userId;

    }
    public function sheets(): array
    {
        $sheets = [];

        // Build query to get all clocks with filtering based on department
        $query = ClockInOut::with('user')
            ->join('users', 'users.id', '=', 'clock_in_outs.user_id')
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->select('clock_in_outs.*')
            ->when($this->department, function ($q) {
                $q->where('departments.name', 'like', '%' . $this->department . '%');
            })
            ->when($this->userId, function ($q) {
                $q->where('users.id', $this->userId);
            });

        // Get distinct user IDs with their clocks
        $userClocks = $query->get()->groupBy('user_id');

        foreach ($userClocks as $userId => $clocks) {
            // Fetch user details for sheet title
            $user = $clocks->first()->user;
            $userName = $user->name ?? 'Unknown';

            // Add sheet for each unique user (should only be one user due to filtering)
            $sheets[] = new UserClocksExportById($clocks, $userName);
        }

        return $sheets;
    }
}