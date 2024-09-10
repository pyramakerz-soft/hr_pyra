<?php

namespace App\Services\Api\Clock;

use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Models\User;
use App\Services\Api\AuthorizationService;
use App\Traits\ClockTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockService
{
    use ResponseTrait, ClockTrait;
    protected $authorizationService;
    protected $exportService;
    protected $filters;

    public function __construct(
        AuthorizationService $authorizationService,
        ClockExportService $exportService,
        array $filters = []
    ) {
        $this->authorizationService = $authorizationService;
        $this->exportService = $exportService;
        $this->filters = $filters;
    }

    public function getAllClocks(Request $request)
    {
        //Check For Hr Role
        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);

        $query = ClockInOut::query();
        // Apply all filters
        foreach ($this->filters as $filter) {
            $query = $filter->apply($query, $request);
        }

        // Handle pagination
        $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found');
        }

        // Handle export request
        if ($request->has('export')) {
            return $this->exportService->exportClocks($clocks, $request->get('department'));
        }

        // Prepare and return data
        $data = $this->prepareClockData($clocks);
        if (!isset($data['clocks'])) {
            return $this->returnError('No Clocks Found');
        }

        return $this->returnData("data", $data, "All Clocks Data");
    }

    public function getUserClocksById(Request $request, User $user)
    {
        // Authorize HR User
        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);

        $query = ClockInOut::where('user_id', $user->id);

        // Apply filters
        foreach ($this->filters as $filter) {
            $query = $filter->apply($query, $request);
        }

        // Handle pagination
        $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found For This User');
        }

        // Handle export request
        if ($request->has('export')) {
            return $this->exportService->exportClocks($clocks, $user->department->name ?? null);
        }

        // Prepare and return data
        $data = $this->prepareClockData($clocks);
        return $this->returnData("data", $data, "Clocks Data for {$user->name}");
    }
    public function showUserClocks(Request $request)
    {
        $authUser = Auth::user();

        $query = ClockInOut::where('user_id', $authUser->id);

        // Apply filters
        foreach ($this->filters as $filter) {
            $query = $filter->apply($query, $request);
        }

        // Handle pagination
        $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found For This User');
        }

        // Handle export request
        if ($request->has('export')) {
            return $this->exportService->exportClocks($clocks, $authUser->department->name ?? null);
        }

        // Prepare and return data
        $data = $this->prepareClockData($clocks);
        return $this->returnData("data", $data, "Clocks Data for {$authUser->name}");
    }
    public function getClockById(ClockInOut $clock)
    {
        return $this->returnData("clock", new ClockResource($clock), "Clock Data");
    }

    public function clockIn(Request $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;

        // Check if user has existing clock-in without clock-out
        if ($this->hasExistingClockInWithoutClockOut($user_id)) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }

        // Validate request for location type and clock-in time
        $this->validateClockInRequest($request);

        if ($request->location_type == "home") {
            return $this->handleHomeClockIn($request, $user_id);
        }

        return $this->handleSiteClockIn($request, $authUser);
    }

    /**
     * Handle clock-out action.
     */
    public function clockOut(Request $request)
    {
        $authUser = Auth::user();
        $clockInOut = $this->getExistingClockInWithoutClockOut($authUser->id);

        if (!$clockInOut) {
            return $this->returnError('You are not clocked in.');
        }

        // Validate clock-out time
        $this->validateClockOutRequest($request);

        // Check if clock-out is valid
        $clockOut = Carbon::parse($request->clock_out);
        if ($clockOut <= Carbon::parse($clockInOut->clock_in)) {
            return $this->returnError("You can't clock out before or at the same time as clock in.");
        }

        if ($clockInOut->location_type == "home") {
            return $this->handleHomeClockOut($clockInOut, $clockOut);
        }

        return $this->handleSiteClockOut($request, $authUser, $clockInOut, $clockOut);
    }

    /**
     * Update user's clock entry.
     */
    public function updateUserClock(Request $request, User $user, ClockInOut $clock)
    {
        // Validate request for clock-in/out times
        $this->validateUpdateClockRequest($request);

        // Check if clock belongs to the user
        $clock = ClockInOut::where('user_id', $user->id)->where('id', $clock->id)->first();
        if (!$clock) {
            return $this->returnError("No clocks found for this user", 404);
        }

        return $this->updateClockEntry($request, $clock, $user);
    }

}
