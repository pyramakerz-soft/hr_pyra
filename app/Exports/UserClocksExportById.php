<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserClocksExportById implements FromCollection, WithHeadings
{
    /**
     * @param Collection $clocks
     */
    public function __construct(public Collection $clocks, public string $userName)
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {

        return $this->clocks->map(function ($clock) {
            $clockIn = Carbon::parse($clock->clock_in)->addHours(3);
            $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out)->addHours(3) : null;
            $now = Carbon::now()->addHours(3);

            if ($clockOut) {
                $totalHours = $clockIn->diffInHours($clockOut);
            } else {
                $totalHours = $clockIn->diffInHours($now);
            }

            return collect([
                'ID' => $clock->id,
                'Day' => $clockIn->format('l'),
                'Date' => $clockIn->format('Y-m-d'),
                'Clock_In' => $clockIn->format('h:iA'),
                'Clock_Out' => $clockOut ? $clockOut->format('h:iA') : null,
                'Total_Hours' => $totalHours,
                'Location_In' => $clock->location_type == "site" && $clock->clock_in ? $clock->location->address : null,
                'Location_Out' => $clock->location_type == "site" && $clock->clock_out ? $clock->location->address : null,
                'User_Name' => $clock->user->name,
                'Site' => $clock->location_type,
                'Formatted_Clock_In' => $clockIn->format('Y-m-d H:i'),
                'Formatted_Clock_Out' => $clockOut ? $clockOut->format('Y-m-d H:i') : null,
            ]);
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
            'User_Name',
            'Site',
            'Formatted_Clock_In',
            'Formatted_Clock_Out',
        ];
    }
    public function title(): string
    {
        return $this->userName;
    }
}
