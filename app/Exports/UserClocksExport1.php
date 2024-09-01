<?php

namespace App\Exports;

use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserClocksExport1 implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ClockResource::collection(ClockInOut::get());
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
