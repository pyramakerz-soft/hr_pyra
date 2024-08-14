<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HrController extends Controller
{
    use ResponseTrait;
    public function assignLocationToUser(Request $request, User $user)
    {
        $auth = Auth::user();
        if (!$auth->hasRole('Hr')) {
            return $this->returnError('User is unauthorized to assign location', 403);
        }
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);
        if (!$user->user_locations()->where('location_id', $validated['location_id'])->exists()) {
            $user->user_locations()->attach($validated['location_id']);
        } else {
            return $this->returnError('User has already been assigned to this location');
        }

        return $this->returnSuccessMessage("Location Assigned Successfully");
    }
}
