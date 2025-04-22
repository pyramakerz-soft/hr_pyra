<?php
namespace Modules\Clocks\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Clocks\Models\ClockInOut;
use Modules\Users\Models\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AbsentUsersExport implements FromCollection, WithHeadings, WithStyles
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        // If no dates are passed, use today's date as both start and end
        $this->startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $this->endDate = $endDate ? Carbon::parse($endDate) : Carbon::today();
    }

    public function collection()
    {
        // Get user IDs with clock-in records in the date range
        $usersWithClockins = ClockInOut::whereBetween('clock_in', [$this->startDate->startOfDay(), $this->endDate->endOfDay()])
            ->pluck('user_id')
            ->unique();

        // Get users who are NOT in that list
        $usersWithoutClockins = User::whereNotIn('id', $usersWithClockins)->get();

        return $usersWithoutClockins->map(function ($user) {
            return [
                'Code' => $user->code,
                'Name' => $user->name,
                'Department' => $user->department ? $user->department->name : 'N/A',
            ];
        });
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Department'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
    }
}
