<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;

class ClockController extends Controller
{

    use ResponseTrait;
    public function clockIn()
    {
        $currentUser = auth()->user();

        $allowedLocations = $currentUser->locations()->toArray();
        dd($allowedLocations);
        return $this->returnData("data", $allowedLocations);
    }
}
