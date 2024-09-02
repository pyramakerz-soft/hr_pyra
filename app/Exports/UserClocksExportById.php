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
    public function __construct(public Collection $clocks)
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
                $realHours = $clockIn->diffInHours($clockOut);
            } else {
                $realHours = $clockIn->diffInHours($now);
            }

            return [
                'ID' => $clock->id,
                'Day' => $clockIn->format('l'),
                'Date' => $clockIn->format('Y-m-d'),
                'Clock_In' => $clockIn->format('h:iA'),
                'Clock_Out' => $clockOut ? $clockOut->format('h:iA') : null,
                'Total_Hours' => $clock->duration ? Carbon::parse($clock->duration)->format('H:i') : null,
                'Location_In' => $clock->location_type == "site" && $clock->clock_in ? $clock->location->address : null,
                'Location_Out' => $clock->location_type == "site" && $clock->clock_out ? $clock->location->address : null,
                'User_ID' => $clock->user_id,
                'Site' => $clock->location_type,
                'Formatted_Clock_In' => $clockIn->format('Y-m-d H:i'),
                'Formatted_Clock_Out' => $clockOut ? $clockOut->format('Y-m-d H:i') : null,
                'Real_Hours' => $realHours + 3, // Add 3 hours to Real Hours
            ];
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
            'Real_Hours', // Add Real Hours heading
        ];
    }
}
