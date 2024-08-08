<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ClockController extends Controller
{

    use ResponseTrait;
    public function clockIn(){
        $currentUser = auth()->user();

        $allowedLocations = $currentUser->locations()->toArray();
        dd($allowedLocations);
        return $this->returnData("data", $allowedLocations);
    }
}
