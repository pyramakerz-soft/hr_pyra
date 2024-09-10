<?php
namespace App\Filters\Api\Clock;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DateFilter implements ClockFilter
{
    public function apply($query, Request $request)
    {
        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'))->toDateString();
            $query->whereDate('clock_in', $date);
        }
        return $query;
    }
}
