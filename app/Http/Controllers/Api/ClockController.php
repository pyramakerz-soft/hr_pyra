<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
// use App\Models\Request;
use App\Models\User;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockController extends Controller
{
    use ResponseTrait;
    use HelperTrait;

    protected function prepareClockData($clocks)
    {
        $isPaginated = $clocks instanceof \Illuminate\Pagination\LengthAwarePaginator;

        $groupedClocks = $clocks->groupBy(function ($clock) {
            return Carbon::parse($clock->clock_in)->toDateString();
        });

        $data = [];

        foreach ($groupedClocks as $date => $clocksForDay) {
            $clocksForDay = $clocksForDay->sortBy(function ($clock) {
                return Carbon::parse($clock->clock_in);
            });

            // Get the first clock of the day
            $firstClockAtTheDay = $clocksForDay->shift();

            // Initialize total duration
            $totalDuration = Carbon::createFromTime(0, 0, 0);

            // Calculate total duration including all clocks
            foreach ($clocksForDay->prepend($firstClockAtTheDay) as $clock) {
                if ($clock->clock_out && $clock->clock_in) {
                    $clockIn = Carbon::parse($clock->clock_in);
                    $clockOut = Carbon::parse($clock->clock_out);
                    $duration = $clockOut->diff($clockIn);

                    $totalDuration->addHours($duration->h)
                        ->addMinutes($duration->i)
                        ->addSeconds($duration->s);
                }
            }

            // Format the total duration
            $totalDurationFormatted = $totalDuration->format('H:i:s');

            // Assign total duration to the first clock
            $firstClockAtTheDay->totalHours = $totalDurationFormatted;

            // Prepare other clocks for the day
            $otherClocksForDay = $clocksForDay->map(function ($clock) {
                return [
                    'id' => $clock->id,
                    'clockIn' => Carbon::parse($clock->clock_in)->format('h:iA'),
                    'clockOut' => $clock->clock_out ? Carbon::parse($clock->clock_out)->format('h:iA') : null,
                ];
            });

            // Prepare the final data structure
            $data[] = (new ClockResource($firstClockAtTheDay))->toArray(request()) + [
                'otherClocks' => $otherClocksForDay->values()->toArray(),
                'totalHours' => $totalDurationFormatted,
            ];
        }

        return [
            'clocks' => $data,
            'pagination' => $isPaginated ? [
                'current_page' => $clocks->currentPage(),
                'next_page_url' => $clocks->nextPageUrl(),
                'previous_page_url' => $clocks->previousPageUrl(),
                'last_page' => $clocks->lastPage(),
                'total' => $clocks->total(),
            ] : null,
        ];
    }

    public function getUserClocksById(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to view users', 403);
        }

        $query = ClockInOut::where('user_id', $user->id);

        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'))->toDateString();
            $query->whereDate('clock_in', $date);
            $clocks = $query->orderBy('clock_in', 'desc')->get();

        } else {
            $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        }

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found For This User');
        }

        $data = $this->prepareClockData($clocks);

        return $this->returnData("data", $data, "Clocks Data for {$user->name}");
    }

    public function clockIn(StoreClockInOutRequest $request)
    {
        $authUser = Auth::user();

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $user_id = $authUser->id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $userLocations = $authUser->user_locations()->get();
        $closestLocation = null;
        $shortestDistance = null;

        foreach ($userLocations as $userLocation) {
            $location_id = $userLocation->pivot['location_id'];
            $userLongitude = $userLocation->longitude;
            $userLatitude = $userLocation->latitude;

            $distance = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

            if (is_null($shortestDistance) || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closestLocation = [
                    'location_id' => $location_id,
                    'distance' => $distance,
                ];
            }
        }

        if (is_null($closestLocation)) {
            return $this->returnError('User is not located at any registered locations.');
        }

        $location_id = $closestLocation['location_id'];
        $distanceBetweenUserAndLocation = $closestLocation['distance'];
        $now = Carbon::now()->addRealHour(3);
        $UserEndTime = Carbon::parse($authUser->user_detail->end_time);
        if ($now >= $UserEndTime) {
            return $this->returnError("Your Shift Is Ended");
        }
        $existingClockIn = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereDate('clock_in', Carbon::today())
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->exists();
        if ($existingClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        if ($distanceBetweenUserAndLocation < 50) {
            $clock = ClockInOut::create([
                'clock_in' => Carbon::now()->addRealHour(3),
                'clock_out' => null,
                'duration' => null,
                'user_id' => $user_id,
                'location_id' => $location_id,
            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        } else {
            return $this->returnError('User is not located at the correct location. lat : ' . $latitude . " / long : " . $longitude);
        }
    }

    public function clockOut(UpdateClockInOutRequest $request)
    {
        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $authUser = Auth::user();

        $ClockauthUser = ClockInOut::where('user_id', $authUser->id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();

        if (!$ClockauthUser) {
            return $this->returnError('You are not clocked in.');
        }

        $user_location = $authUser->user_locations()->wherePivot('location_id', $ClockauthUser->location_id)->first();

        if (!$user_location) {
            return $this->returnError('User is not located at the correct location.');
        }

        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;

        $distanceBetweenUserAndLocation = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

        if ($distanceBetweenUserAndLocation < 50) {
            $clock_in = Carbon::parse($ClockauthUser->clock_in);
            $clock_out = Carbon::now()->addRealHour(3);
            $durationInterval = $clock_out->diffAsCarbonInterval($clock_in);
            $durationFormatted = $durationInterval->format('%H:%I:%S');
            $ClockauthUser->update([
                'clock_out' => $clock_out,
                'duration' => $durationFormatted,
            ]);

            return $this->returnData("clock", $ClockauthUser, "Clock Out Done");
        } else {
            return $this->returnError('User is not located at the correct location. lat : ' . $latitude . " / long : " . $longitude);
        }
    }

    public function showUserClocks(Request $request)
    {
        $authUser = Auth::user();

        $query = ClockInOut::where('user_id', $authUser->id);

        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'))->toDateString();
            $query->whereDate('clock_in', $date);
        }
        $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found For This User');
        }

        $data = $this->prepareClockData($clocks);

        return $this->returnData("data", $data, "Clocks Data for {$authUser->name}");
    }

    public function updateUserClock(Request $request, User $user, ClockInOut $clock)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to update users', 403);
        }

        $this->validate($request, [
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
        ]);

        $clock = ClockInOut::where('user_id', $user->id)->where('id', $clock->id)->first();
        if (!$clock) {
            return $this->returnError("No clocks found for this user", 404);
        }

        // $currentDate = Carbon::now()->format('Y-m-d');
        $existingDate = Carbon::parse($clock->clock_in)->format('Y-m-d');

        $clockIn = $request->clock_in ? Carbon::createFromFormat('Y-m-d H:i:s', "{$existingDate} {$request->clock_in}:00") : Carbon::parse($clock->clock_in);
        $clockOut = $request->clock_out ? Carbon::createFromFormat('Y-m-d H:i:s', "{$existingDate} {$request->clock_out}:00") : Carbon::parse($clock->clock_out);
        if ($clockOut->isSameDay($clockIn) === false) {
            return $this->returnError("clock_in and clock_out must be on the same day", 400);
        }

        $durationFormatted = $clockIn->diff($clockOut)->format('%H:%I:%S');

        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $durationFormatted,
        ]);

        return $this->returnData("clock", new ClockResource($clock), "Clock data for {$user->name}");
    }

}
