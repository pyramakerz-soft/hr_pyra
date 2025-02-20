<?php
namespace Modules\Clocks\Filters\Api;

use Illuminate\Http\Request;

interface ClockFilter
{
    public function apply($query, Request $request);
}