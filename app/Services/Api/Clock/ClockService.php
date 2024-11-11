<?php

namespace App\Services\Api\Clock;

use App\Http\Requests\Api\AddClockRequest;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Http\Requests\Api\UpdateClockRequest;
use App\Http\Resources\Api\ClockResource;
use App\Http\Resources\Api\IssueResource;
use App\Models\ClockInOut;
use App\Models\User;
use App\Traits\ClockInTrait;
use App\Traits\ClockOutTrait;
use App\Traits\ClockTrait;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ClockService
{
    use ResponseTrait, ClockTrait, ClockInTrait, ClockOutTrait, HelperTrait;
    protected $exportService;
    protected $filters;

    public function __construct(
        ClockExportService $exportService,
        array $filters = []
    ) {
        $this->exportService = $exportService;
        $this->filters = $filters;
    }

    public function getAllClocks(Request $request)
    {

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
        //1- Check If user already Clocked in
        $authUser = Auth::user();
        $user_id = $authUser->id;
        $clock_in = $request->clock_in;
        //2- Check if the user has already clocked in today
        if ($this->checkClockInWithoutClockOut($user_id, $clock_in)) {
            return $this->returnError('You have already clocked in today.');
        }

        //4- Handle home clock-in if location_type is 'home'
        if ($request->location_type == 'home') {
            return $this->handleHomeClockIn($request, $user_id);
        }

        //5- Handle site clock-in if location_type is 'site'
        return $this->handleSiteClockIn($request, $authUser);
    }

    public function clockOut(ClockOutRequest $request)
    {
        //1- Retrieve the clock that the user has clocked_In
        $authUser = Auth::user();
        $user_id = $authUser->id;
        $clock = $this->getClockInWithoutClockOut($user_id);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }
        //2- Validate the clock time
        $clockIn = carbon::parse($clock->clock_in);
        $clockOut = Carbon::parse($request->clock_out);
        $this->validateClockTime($clockIn, $clockOut);

        //3- Check for location_type is "site" OR "home"
        if ($clock->location_type == "home") {
            return $this->handleHomeClockOut($clock, $clockOut);
        }

        return $this->handleSiteClockOut($request, $authUser, $clock, $clockOut);
    }

    public function updateUserClock(UpdateClockRequest $request, User $user, ClockInOut $clock)
    {

        //1- Check if clock belongs to the user
        $clock = $this->getUserClock($user->id, $clock->id);
        if (!$clock) {
            return $this->returnError("No clocks found for this user", 404);
        }

        //2- Update the clock
        if ($clock->location_type == 'home') {
            return $this->updateHomeClock($request, $clock, $user);
        }
        return $this->updateSiteClock($request, $clock, $user);

    }

    public function AddClockByHr(AddClockRequest $request, User $user)
    {

        //1- Check if the user has an existing clock-in without a clock-out
        if ($this->checkExistingClockInWithoutClockOut($user->id)) {
            return $this->returnError('You already have an existing clock-in without clocking out.');
        }

        //2- Handle home clock-in if location_type is 'home'
        if ($request->location_type == 'home') {
            return $this->handleHomeClockIn($request, $user->id);
        }

        //3- Handle site clock-in if location_type is 'site'
        return $this->handleSiteClockInByHr($request, $user);
    }
    public function getClockIssues(Request $request)
    {
        if ($request->has('month')) {
            $month = Carbon::parse($request->get('month'));
            $startOfMonth = (clone $month)->startOfMonth()->startOfDay();
            $endOfMonth = (clone $month)->endOfMonth()->endOfDay();

        } else {
            $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
            $endOfMonth = Carbon::now()->endOfMonth()->endOfDay();
        }
        $query = ClockInOut::where('is_issue', true)
            ->whereBetween('clock_in', [$startOfMonth, $endOfMonth])
            ->orderBy('clock_in', 'Desc');

        $filtersApplied = $request->has('date');

        foreach ($this->filters as $filter) {
            $query = $filter->apply($query, $request);
        }

        if ($filtersApplied) {
            $clocks = $query->get();
        } else {
            $clocks = $query->paginate(7);
        }
        if ($clocks->isEmpty()) {
            return $this->returnError('No Clock Issues Found');
        }
        return $filtersApplied
            ? $this->returnData('clockIssues', IssueResource::collection($clocks))
            : $this->returnData('clockIssues', IssueResource::collectionWithPagination($clocks));

    }
    public function getCountIssues()
    {
        $totalIssueCount['count'] = ClockInOut::where('is_issue', true)
            ->count();
        return $this->returnData('data', $totalIssueCount, 'Count of Issues');
    }
    public function updateClockIssues(Request $request, ClockInOut $clock)
    {

        if (!$clock->is_issue) {
            return $this->returnError('There is no issue for this clock');
        }
        $clock->update([
            'is_issue' => false,
        ]);

        return $this->returnData('clock', $clock, 'Clock Issue Updated Successfully');
    }

}