<?php
namespace App\Filters\Api\Clock;

use Illuminate\Http\Request;

interface ClockFilter
{
    public function apply($query, Request $request);
}