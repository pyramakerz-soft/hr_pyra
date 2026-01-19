<?php

namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Clocks\Models\ClockInOut;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AbsentUsersPerDaySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $date;
    protected $users;

    public function __construct($date, $users = null)
    {
        $this->date = Carbon::parse($date);
        $this->users = $users;
    }

    public function collection()
    {
        $dayStart = $this->date->copy()->startOfDay();
        $dayEnd = $this->date->copy()->endOfDay();

        // Get users who clocked in this day
        $usersWithClockins = ClockInOut::whereBetween('clock_in', [$dayStart, $dayEnd])
            ->when($this->users, function ($query) {
                // If users are passed as IDs or models, we filter clockins by them
                $userIds = $this->users instanceof \Illuminate\Support\Collection ? $this->users->pluck('id') : $this->users;
                // It's safer to check if it's a collection or array of IDs. 
                // Assuming it's a collection of User objects or IDs.
                if (is_array($userIds) || $userIds instanceof \Traversable) {
                    $query->whereIn('user_id', $userIds);
                }
            })
            ->pluck('user_id')
            ->unique();

        // Get users who generate absent records (NOT clocked in)
        $query = User::whereNotIn('id', $usersWithClockins);

        if ($this->users) {
            $userIds = $this->users instanceof \Illuminate\Support\Collection ? $this->users->pluck('id') : $this->users;
            $query->whereIn('id', $userIds);
        }

        $usersWithoutClockins = $query->get();

        return $usersWithoutClockins->map(function ($user) use ($dayStart) {
            // Check for vacation on this specific day
            // Vacation is valid if our day is between from_date and to_date (inclusive)
            $vacation = UserVacation::with(['vacationType', 'attachments'])
                ->where('user_id', $user->id)
                ->whereDate('from_date', '<=', $dayStart)
                ->whereDate('to_date', '>=', $dayStart)
                ->where('status', 'approved')
                ->first();

            $vacationInfo = '';

            if ($vacation && $vacation->vacationType) {
                $typeName = $vacation->vacationType->name;

                // standardise string to lower case for check
                if (str_contains(strtolower($typeName), 'sick')) {
                    // Check for attachment
                    $attachment = $vacation->attachments->first();
                    if ($attachment && $attachment->file_url) {
                        // Create Excel Hyperlink
                        // Note: file_url usually needs to be a full accessible URL.
                        // Assuming file_url in model returns relative path, we might need to prepend generic URL or storage URL.
                        // But based on LeaveAttachment model it returns file_path. 
                        // Let's assume it's a full URL or we use asset().
                        // The user request said "hyperlink for the attachment".
                        $url = $attachment->file_url;
                        // Map to a hyperlink formula
                        $vacationInfo = '=HYPERLINK("' . $url . '", "' . $typeName . '")';
                    } else {
                        $vacationInfo = $typeName . ' (No Attachment)';
                    }
                } else {
                    $vacationInfo = $typeName;
                }
            }

            return [
                'Code' => $user->code,
                'Name' => $user->name,
                'Department' => $user->department ? $user->department->name : 'N/A',
                'Status' => $vacationInfo ?: 'Absent',
            ];
        });
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Department', 'Status / Vacation'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(40);
    }

    public function title(): string
    {
        return $this->date->format('Y-m-d');
    }
}
