<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClocksExport;
use App\Exports\UserClocksExportById;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClockInOutRequest;
use App\Http\Requests\UpdateClockInOutRequest;
// use App\Models\Request;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

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

            $firstClockAtTheDay = $clocksForDay->last();
            $totalDurationInSeconds = 0;

            foreach ($clocksForDay as $clock) {
                if ($clock->clock_in) {
                    $clockIn = Carbon::parse($clock->clock_in);

                    if ($clock->clock_out) {
                        $clockOut = Carbon::parse($clock->clock_out);
                        $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    } else {
                        $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    }

                    $totalDurationInSeconds += $durationInSeconds;

                    $clock->duration = gmdate('H:i:s', $durationInSeconds);
                }
            }

            $totalDurationFormatted = gmdate('H:i:s', $totalDurationInSeconds);

            $firstClockAtTheDay->duration = $totalDurationFormatted;

            $otherClocksForDay = $clocksForDay->skip(1)->map(function ($clock) {
                $clockIn = $clock->clock_in ? Carbon::parse($clock->clock_in) : null;
                $clockOut = $clock->clock_out ? Carbon::parse($clock->clock_out) : null;
                $totalHours = null;

                if ($clockIn && $clockOut) {
                    $durationInSeconds = $clockIn->diffInSeconds($clockOut);
                    $totalHours = gmdate('H:i', $durationInSeconds);
                } elseif ($clockIn) {
                    $durationInSeconds = $clockIn->diffInSeconds(Carbon::now());
                    $totalHours = gmdate('H:i', $durationInSeconds);
                }

                return [
                    'id' => $clock->id,
                    'clockIn' => $clockIn ? $clockIn->format('H:i') : null,
                    'clockOut' => $clockOut ? $clockOut->format('H:i') : null,
                    'totalHours' => $totalHours,
                    'site' => $clock->location_type,
                    'location_in' => $clock->location->address ?? null,
                    'location_out' => $clock->location->address ?? null,
                ];
            });

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

    public function allClocks(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to view user clocks', 403);
        }

        $query = ClockInOut::query();

        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'))->toDateString();
            $query->whereDate('clock_in', $date);
            $clocks = $query->orderBy('clock_in', 'desc')->get();
        } else if ($request->has('month')) {

            $month = Carbon::parse($request->get('month'));

            $startOfMonth = $month->copy()->subMonth()->startOfMonth()->addDays(25);
            $endOfMonth = $month->copy()->startOfMonth()->addDays(25);

            $query->whereBetween('clock_in', [$startOfMonth, $endOfMonth]);
            $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);
        } else {
            $now = Carbon::now();

            $currentStart = $now->copy()->subMonth()->startOfMonth()->addDays(25);
            $currentEnd = $now->copy()->startOfMonth()->addDays(25);

            // Filter based on the current custom month range
            $query->whereBetween('clock_in', [$currentStart, $currentEnd]);

            // Paginate and sort by clock_in in descending order
            $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);
        }

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found');
        }

        if ($request->has('export')) {

            $clocksCollection = $clocks instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $clocks->getCollection()
            : $clocks;
            return (new ClocksExport)->download('all_user_clocks.xlsx');

        }

        $data = $this->prepareClockData($clocks);

        if (!isset($data['clocks'])) {
            return $this->returnError('No Clocks Found');
        }

        return $this->returnData("data", $data, "All Clocks Data");
    }
    public function getUserClocksById(Request $request, User $user)
    {
        $authUser = Auth::user();
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to view user clocks', 403);
        }

        $query = ClockInOut::where('user_id', $user->id);

        if ($request->has('date')) {
            $date = Carbon::parse($request->get('date'))->toDateString();
            $query->whereDate('clock_in', $date);
            $clocks = $query->orderBy('clock_in', 'desc')->get();

        } else if ($request->has('month')) {
            $month = Carbon::parse($request->get('month'));

            $startOfMonth = $month->copy()->subMonth()->startOfMonth()->addDays(25);

            $endOfMonth = $month->copy()->startOfMonth()->addDays(25);

            $query->whereBetween('clock_in', [$startOfMonth, $endOfMonth]);

            $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);
        } else {

            $now = Carbon::now();

            $currentStart = $now->copy()->subMonth()->startOfMonth()->addDays(25);
            $currentEnd = $now->copy()->startOfMonth()->addDays(25);

            // Filter based on the current custom month range
            $query->whereBetween('clock_in', [$currentStart, $currentEnd]);

            // Paginate and sort by clock_in in descending order
            $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);
        }

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found For This User');
        }

        if ($request->has('export')) {
            $clocksCollection = ($clocks->getCollection());
            return Excel::download(new UserClocksExportById($clocksCollection, $user->name), 'user_clocks_' . $user->name . '.xlsx');

        }
        $data = $this->prepareClockData($clocks);

        if (!isset($data['clocks'])) {
            return $this->returnError('No Clocks Found For This User');
        }
        return $this->returnData("data", $data, "Clocks Data for {$user->name}");
    }

    public function clockIn(StoreClockInOutRequest $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;

        $existingClockInWithoutClockOut = ClockInOut::where('user_id', $user_id)
            ->whereNull('clock_out')
            ->exists();

        if ($existingClockInWithoutClockOut) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }
        $this->validate($request, [
            'location_type' => 'required|string|exists:work_types,name',
            'clock_in' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        if ($request->location_type == "home") {

            $existingHomeClockIn = ClockInOut::where('user_id', $user_id)
                ->whereDate('clock_in', Carbon::today())
                ->where('location_type', "home")
                ->whereNull('clock_out')
                ->orderBy('clock_in', 'desc')
                ->exists();
            if ($existingHomeClockIn) {
                return $this->returnError('You have already clocked in.');
            }

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');
            $clock = ClockInOut::create([
                'clock_in' => $clockIn,
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user_id,
                'location_id' => null,
                'location_type' => $request->location_type,
            ]);
            return $this->returnData("clock", $clock, "Clock In Done");
        }

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
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
            // dd($location_id);
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
        // $now = Carbon::now()->addRealHour(3);
        // $UserEndTime = Carbon::parse($authUser->user_detail->end_time);

        $existingSiteClockIn = ClockInOut::where('user_id', $user_id)
            ->where('location_id', $location_id)
            ->whereDate('clock_in', Carbon::today())
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->exists();
        if ($existingSiteClockIn) {
            return $this->returnError('You have already clocked in.');
        }

        if ($distanceBetweenUserAndLocation < 50) {

            $clockIn = Carbon::parse($request->clock_in);
            $durationInterval = $clockIn->diffAsCarbonInterval(Carbon::now());
            $durationFormatted = $durationInterval->format('%H:%I:%S');

            $clock = ClockInOut::create([
                'clock_in' => Carbon::parse($request->clock_in),
                'clock_out' => null,
                'duration' => $durationFormatted,
                'user_id' => $user_id,
                'location_id' => $location_id,
                'location_type' => $request->location_type,

            ]);

            return $this->returnData("clock", $clock, "Clock In Done");
        } else {
            return $this->returnError('User is not located at the correct location. lat : ' . $latitude . " / long : " . $longitude);
        }
    }

    public function clockOut(UpdateClockInOutRequest $request)
    {
        $authUser = Auth::user();
        $ClockauthUser = ClockInOut::where('user_id', $authUser->id)
            ->whereNull('clock_out')
            ->orderBy('clock_in', 'desc')
            ->first();
        $request->validate([
            'clock_out' => ['required', "date_format:Y-m-d H:i:s"],
        ]);
        if (!$ClockauthUser) {
            return $this->returnError('You are not clocked in.');
        }

        $clockOut = Carbon::parse($request->clock_out);
        if ($clockOut <= Carbon::parse($ClockauthUser->clock_in)) {
            return $this->returnError("You can't clock out before or at the same time as clock in.");
        }
        if ($ClockauthUser->location_type == "home") {
            $clock_in = Carbon::parse($ClockauthUser->clock_in);
            $clock_out = $clockOut;
            $durationInterval = $clock_in->diffAsCarbonInterval($clockOut);
            $durationFormatted = $durationInterval->format('%H:%I:%S');
            $ClockauthUser->update([
                'clock_out' => $clock_out,
                'duration' => $durationFormatted,
            ]);
            return $this->returnData("clock", $ClockauthUser, "Clock Out Done");

        }
        $user_location = $authUser->user_locations()->wherePivot('location_id', $ClockauthUser->location_id)->first();
        if (!$user_location) {
            return $this->returnError('User is not located at the correct location.');
        }

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        $latitude = $request->latitude;
        $longitude = $request->longitude;

        $userLongitude = $user_location->longitude;
        $userLatitude = $user_location->latitude;

        $distanceBetweenUserAndLocation = $this->haversineDistance($userLatitude, $userLongitude, $latitude, $longitude);

        if ($distanceBetweenUserAndLocation < 50) {
            $clock_in = Carbon::parse($ClockauthUser->clock_in);
            $durationInterval = $clock_in->diffAsCarbonInterval($clockOut);
            $durationFormatted = $durationInterval->format('%H:%I:%S');
            $ClockauthUser->update([
                'clock_out' => $clockOut,
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
            'clock_in' => ['nullable', 'date_format:Y-m-d H:i'],
            'clock_out' => ['nullable', 'date_format:Y-m-d H:i'],
        ]);

        $clock = ClockInOut::where('user_id', $user->id)->where('id', $clock->id)->first();
        if (!$clock) {
            return $this->returnError("No clocks found for this user", 404);
        }

        $existingDate = Carbon::parse($clock->clock_in)->format('Y-m-d');

        // Adjust clock_in time if provided
        if ($request->clock_in) {
            $clockIn = Carbon::createFromFormat('Y-m-d H:i', $request->clock_in);
        } else {
            $clockIn = Carbon::parse($clock->clock_in);
        }

        // Adjust clock_out time if provided
        if ($request->clock_out) {
            $clockOut = Carbon::createFromFormat('Y-m-d H:i', $request->clock_out);
        } else {
            $clockOut = Carbon::parse($clock->clock_out);
        }

        // Check if clock_in and clock_out are on the same day
        if (!$clockOut->isSameDay($clockIn)) {
            return $this->returnError("clock_in and clock_out must be on the same day", 400);
        }

        // Ensure clock_in is before clock_out
        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            return $this->returnError("clock_out must be after clock_in", 400);
        }

        // Calculate duration
        $durationFormatted = $clockIn->diff($clockOut)->format('%H:%I:%S');

        // Update clock record
        $clock->update([
            'clock_in' => $clockIn->format('Y-m-d H:i:s'),
            'clock_out' => $clockOut->format('Y-m-d H:i:s'),
            'duration' => $durationFormatted,
        ]);
        return $this->returnData("clock", new ClockResource($clock), "Clock data for {$user->name}");
    }

}
