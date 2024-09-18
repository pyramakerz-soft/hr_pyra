<?php

namespace App\Services\Api\Clock;

use App\Http\Requests\Api\AddClockRequest;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Http\Requests\Api\UpdateClockRequest;
use App\Http\Resources\ClockResource;
use App\Models\ClockInOut;
use App\Models\User;
use App\Services\Api\AuthorizationService;
use App\Traits\ClockInTrait;
use App\Traits\ClockOutTrait;
use App\Traits\ClockTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockService
{
    use ResponseTrait, ClockTrait, ClockInTrait, ClockOutTrait;
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
            return $this->exportService->exportClocks($clocks, $user->department->name ?? null, $user->id);
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

    public function clockIn(ClockInRequest $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;

        // Handle home clock-in if location_type is 'home'
        if ($request->location_type == 'home') {
            return $this->handleHomeClockIn($request, $user_id);
        }

        // Handle site clock-in if location_type is 'site'
        return $this->handleSiteClockIn($request, $authUser);
    }
    /**
     * Handle clock-out action.
     */
    public function clockOut(ClockOutRequest $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;

        $clock = $this->getClockInWithoutClockOut($user_id);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }

        $clockOut = Carbon::parse($request->clock_out);
        if ($clock->location_type == "home") {
            return $this->handleHomeClockOut($clock, $clockOut);
        }

        return $this->handleSiteClockOut($request, $authUser, $clock, $clockOut);
    }

    /**
     * Update user's clock entry.
     */
    public function updateUserClock(UpdateClockRequest $request, User $user, ClockInOut $clock)
    {
        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);
        // Check if clock belongs to the user
        $clock = ClockInOut::where('user_id', $user->id)->where('id', $clock->id)->first();
        if (!$clock) {
            return $this->returnError("No clocks found for this user", 404);
        }

        return $this->updateClockEntry($request, $clock, $user);
    }

    public function AddClockByHr(AddClockRequest $request, User $user)
    {
        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);
        // Check if the user has an existing clock-in without a clock-out
        if ($this->checkExistingClockInWithoutClockOut($user->id)) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }

        // Handle home clock-in if location_type is 'home'
        if ($request->location_type == 'home') {
            return $this->handleHomeClockIn($request, $user->id);
        }

        // Handle site clock-in if location_type is 'site'
        return $this->handleAddSiteClockByHr($request, $user);
    }
}
