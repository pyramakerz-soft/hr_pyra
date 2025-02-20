<?php
namespace Modules\Clocks\Filters\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MonthFilter implements ClockFilter
{
    public function apply($query, Request $request)
    {
        if ($request->has('month')) {
            $month = Carbon::parse($request->get('month'));

            $startOfMonth = $month->copy()->subMonth()->startOfMonth()->addDays(25);

            $endOfMonth = $month->endOfMonth()->endOfDay();

            $query->whereBetween('clock_in', [$startOfMonth, $endOfMonth]);
        }
        return $query;
    }
}
