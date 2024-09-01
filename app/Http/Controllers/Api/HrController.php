<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HrController extends Controller
{
    use ResponseTrait;
    public function getLocationAssignedToUser(User $user)
    {

        $users = User::with('user_locations')->where('id', $user->id)->get();
        $data = [];

        foreach ($users as $user) {
            foreach ($user->user_locations as $location) {
                $data[] = [
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                ];
            }

        }
        return $this->returnData('userLocations', $data, 'User Location Data');

    }
    public function getWorkTypeAssignedToUser()
    {
        $users = User::with('work_types')->get();
        $data = [];
        foreach ($users as $user) {
            foreach ($user->work_types as $work_type) {
                $pivotData = $work_type->pivot->toArray();
                $data[] = ['user_work_type' => $pivotData];
            }
        }
        return $this->returnData('user_work_types', $data, 'User WorkType Data');
    }
    public function hrClockIn(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to clock in for this user', 403);
        }
        $existingClockInWithoutClockOut = ClockInOut::where('user_id', $user->id)
            ->whereNull('clock_out')
            ->exists();

        if ($existingClockInWithoutClockOut) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }

        $request->validate([
            'location_type' => "required|string|exists:work_types,name",
            'clock_in' => ['required', 'date_format:Y-m-d H:i:s'],
            'location_id' => 'required_if:location_type,site|exists:locations,id',
        ]);

        $location_type = $request->location_type;

        if ($location_type == "home") {
            $existingHomeClockIn = ClockInOut::where('user_id', $user->id)
                ->whereDate('clock_in', Carbon::today())
                ->where('location_type', "home")
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->exists();

            if ($existingHomeClockIn) {
                return $this->returnError('The user has already clocked in from home.');
            }

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user->id,
                'location_id' => null,
                'location_type' => $location_type,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        }

        if ($location_type == "site") {
            $location_id = $request->location_id;

            $userLocation = $user->user_locations()->where('location_id', $location_id)->exists();

            if (!$userLocation) {
                return $this->returnError('The specified location is not assigned to the user.');
            }

            $existingSiteClockIn = ClockInOut::where('user_id', $user->id)
                ->where('location_id', $location_id)
                ->where('location_type', 'site')
                ->whereDate('clock_in', Carbon::today())
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->exists();

            if ($existingSiteClockIn) {
                return $this->returnError('The user has already clocked in from the site.');
            }

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user->id,
                'location_id' => $location_id,
                'location_type' => $location_type,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        }

        return $this->returnError('Invalid location type provided.');
    }

    // public function assignLocationToUser(Request $request, User $user)
    // {
    //     // $auth = Auth::user();
    //     // if (!$auth->hasRole('Hr')) {
    //     //     return $this->returnError('User is unauthorized to assign location', 403);
    //     // }
    //     // $this->validate($request, [
    //     //     'location_id' => 'required|exists:locations,id',

    //     // ]);

    //     // $LocationAssignedToUser = $user->user_locations()->wherePivot('location_id', $request->location_id)->first();
    //     // if ($LocationAssignedToUser) {
    //     //     return $this->returnError('User has already been assigned to this location');
    //     // }
    //     // $user->user_locations()->attach($request->location_id);
    //     // return $this->returnSuccessMessage('Location Assigned Successfully To User');

    // }
    // public function assignWorkTypeToUser(Request $request, User $user)
    // {
    //     // $this->validate($request, [
    //     //     'work_type_id' => 'required|exists:work_types,id',
    //     // ]);
    //     // $workTypeAssignedToUser = $user->work_types()->where('work_type_id', $request->work_type_id)->exists();
    //     // if ($workTypeAssignedToUser) {
    //     //     return $this->returnError('User has already been assigned to this workType');
    //     // }
    //     // $user->work_types()->attach($request->work_type_id);
    //     // return $this->returnSuccessMessage('WorkType Assigned Successfully to User');
    // }
}
