<?php
namespace App\Filters\Api\Clock;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthFilter implements ClockFilter
{
    public function apply($query, Request $request)
    {
        if ($request->has('month')) {

            $month = Carbon::parse($request->get('month'));

            $startOfMonth = $month->copy()->subMonth()->startOfMonth()->addDays(25);
            $endOfMonth = $month->copy()->startOfMonth()->addDays(25);

            $query->whereBetween('clock_in', [$startOfMonth, $endOfMonth]);
        }
        return $query;
    }
}
