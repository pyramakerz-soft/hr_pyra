<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use App\Traits\ResponseTrait;

class HrController extends Controller
{
    use ResponseTrait;
    public function assignLocationToUser(User $user, Location $location)
    {
        dd($user->toArray());
        $user->locations()->attach($location);
        return $this->returnSuccessMessage("Location Assigned Successfully");
    }
}
