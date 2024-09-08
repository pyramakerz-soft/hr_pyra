<?php

namespace App\Traits;

use App\Http\Resources\ClockResource;
use Illuminate\Support\Carbon;

trait ClockTrait
{
    // protected function paginateArray(array $items, int $perPage)
    // {
    //     $page = LengthAwarePaginator::resolveCurrentPage(); // Resolve the current page
    //     $items = collect($items); // Convert array to collection
    //     $total = $items->count(); // Total number of items
    //     $items = $items->forPage($page, $perPage)->values(); // Paginate items

    //     return new LengthAwarePaginator($items, $total, $perPage, $page, [
    //         'path' => LengthAwarePaginator::resolveCurrentPath(), // Keep the current URL
    //         'pageName' => 'page', // Ensure the pagination query string parameter is 'page'
    //     ]);
    // }

    protected function prepareClockData($clocks)
    {
        // Ensure $clocks is paginated
        $isPaginated = $clocks instanceof \Illuminate\Pagination\LengthAwarePaginator;
        // dd($clocks->toArray());
        // Group clocks by date
        $groupedClocks = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString();
        });

        $data = [];
        foreach ($groupedClocks as $date => $clocksForDay) {
            if (!$clocksForDay) {
                continue;
            }

            // Sort clocks by clock_in
            $clocksForDay = $clocksForDay->sortByDesc(function ($clock) {
                return Carbon::parse($clock->clock_in);
            });
            // dd($clocksForDay->toArray());
            // First clock of the day
            $firstClockAtTheDay = $clocksForDay->first();

            // Process other clocks of the day
            $otherClocksForDay = $clocksForDay->filter(function ($clock) use ($firstClockAtTheDay) {
                return $clock->id !== $firstClockAtTheDay->id;
            })->sortBy(function ($clock) {
                return Carbon::parse($clock->clock_in);
            })->map(function ($clock) {
                $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;
                $totalHours = null;

                if ($clockIn && $clockOut) {
                    $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    $totalHours = gmdate('H:i', $durationInSeconds);
                } elseif ($clockIn) {
                    $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    $totalHours = gmdate('H:i', $durationInSeconds);
                }

                return [
                    'id' => $clock->id,
                    'clockIn' => $clockIn ? $clockIn->format('H:i') : null,
                    'clockOut' => $clockOut ? $clockOut->format('H:i') : null,
                    'totalHours' => $totalHours,
                    'site' => $clock->location_type,
                    'location_in' => $clock->location->address ?? null,
                    'location_out' => $clock->location->address ?? null,
                    'formattedClockIn' => $clockIn ? $clockIn->format('Y-m-d H:i') : null,
                    'formattedClockOut' => $clockOut ? $clockOut->format('Y-m-d H:i') : null,
                ];
            });

            // Calculate total duration
            $totalDurationInSeconds = 0;
            foreach ($clocksForDay as $clock) {
                if ($clock->clock_in) {
                    $clockIn = Carbon::parse($clock->clock_in);

                    if ($clock->clock_out) {
                        $clockOut = Carbon::parse($clock->clock_out);
                        $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    } else {
                        $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    }

                    $totalDurationInSeconds += $durationInSeconds;
                    $clock->duration = gmdate('H:i:s', $durationInSeconds);
                }
            }

            $totalDurationFormatted = gmdate('H:i:s', $totalDurationInSeconds);
            $firstClockAtTheDay->duration = $totalDurationFormatted;

            $data[] = (new ClockResource($firstClockAtTheDay))->toArray(request()) + [
                'otherClocks' => $otherClocksForDay->values()->toArray(),
                'totalHours' => $totalDurationFormatted,
            ];
        }

        return [
            'clocks' => $data,
            'pagination' => $isPaginated ? [
                'current_page' => $clocks->currentPage(),
                'next_page_url' => $clocks->nextPageUrl(),
                'previous_page_url' => $clocks->previousPageUrl(),
                'last_page' => $clocks->lastPage(),
                'total' => $clocks->total(),
            ] : null,
        ];
    }

}