<?php
namespace App\Exports;

use App\Http\Resources\Api\ClockResource;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserClocksExport implements FromCollection, WithHeadings
{
    /**
     * @param int $userId
     */
    public function __construct(public Collection $clocks)
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->clocks->map(function ($clock) {
            return ClockResource::collection($clock);
        });
    }

    /**
     * Define the headings for the Excel file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Day',
            'Date',
            'Clock_In',
            'Clock_Out',
            'Total_Hours',
            'Location_In',
            'Location_Out',
            'User_ID',
            'Site',
            'Formatted_Clock_In',
            'Formatted_Clock_Out',
        ];
    }
}
