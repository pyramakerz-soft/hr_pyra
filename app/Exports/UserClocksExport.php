<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserClocksExport implements FromArray, WithHeadings, WithMapping
{
    protected $clocksData;

    public function __construct(array $clocksData)
    {
        $this->clocksData = $clocksData;
    }

    /**
     * Prepare the array data for export.
     *
     * @return array
     */
    public function array(): array
    {
        return $this->clocksData;
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

        ];
    }

    /**
     * Map the data to match the structure.
     *
     * @param array $clock
     * @return array
     */
    public function map($clock): array
    {
        $clockIn = Carbon::parse($clock['clockIn']);
        $clockOut = $clock['clockOut'] ? Carbon::parse($clock['clockOut'])->format('h:iA') : null;
        $locationOut = $clock['locationOut'] ?? null;
        $locationIn = $clock['locationIn'] ?? null;
        $totalHours = $clock['totalHours'] ? Carbon::parse($clock['totalHours'])->format('H:i') : null;
        $day = $clockIn->format('l');
        $date = $clockIn->format('Y-m-d');
        dd($date);
        return [
            $clock['id'],
            $day, // Day
            $date, // Date
            $clockIn->format('h:iA'), // Clock In
            $clockOut,
            $totalHours,
            $locationIn,
            $locationOut,
            $clock['userId'],
            $clock['site'],

        ];
    }
}
