<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HrController extends Controller
{
    use ResponseTrait;
    public function assignLocationToUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);
        if (!$user->locations()->where('location_id', $validated['location_id'])->exists()) {
            $user->user_locations()->attach($validated['location_id']);
        } else {
            $this->returnError('User has already been assigned to this location');
        }

        return $this->returnSuccessMessage("Location Assigned Successfully");
    }
}
