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
use App\Models\AppVersion;
use App\Traits\ClockInTrait;
use App\Traits\ClockOutTrait;
use App\Traits\ClockTrait;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Log;
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
    // Get the authenticated user
    $authUser = Auth::user();
    $user_id = $authUser->id;
    $clock_in = $request->clock_in;

    $arr = ['type'=>'In','version'=> $request->version,'lat' => $request->latitude,'lng' => $request->longitude,'user' => $authUser->email];
\Log::info($arr);
    if(!$request->version  && ! App::environment('local'))
    return response()->json(['message' => 'Please update the application to the latest version.'], 406);
    
    // Determine the latest version based on the platform (Android/iOS)
    $platformType = $request->isAndroid ? 'android' : 'ios';
    $latestVersion = AppVersion::where('type', $platformType)->orderBy('version', 'desc')->value('version');

    // Check if the request's version is outdated
    if ($request->version != $latestVersion   &&  ! App::environment('local')) {
        return response()->json(['message' => 'Please update the application to the latest version.'], 406);
        // throw new \Exception('', 406);
    }
    
    if ($request->mob) {
        if (is_null($authUser->mob)) {
            $authUser->update(['mob' => $request->mob]);
        } elseif ($authUser->mob !== $request->mob) {
            return response()->json(['message' => 'Your current mobile is different from the original logged-in phone ('.$authUser->mob.')('.$request->mob.')'], 406);
        }
    }

    // 1- Check If user already clocked in today
    if ($this->checkClockInWithoutClockOut($user_id, $clock_in)) {
        return $this->returnError('You have already clocked in today.');
    }

    // 2- Handle home clock-in if location_type is 'home'
    if ($request->location_type == 'home') {
        return $this->handleHomeClockIn($request, $user_id);
    }

    // 3- Handle float clock-in if location_type is 'float'
    if ($request->location_type == 'float') {
        return $this->handleFloatClockIn($request, $user_id);
    }

    // 4- Handle site clock-in if location_type is 'site'
    return $this->handleSiteClockIn($request, $authUser);
}


    public function clockOut(ClockOutRequest $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;
        $clock = $this->getClockInWithoutClockOut($user_id);
        $arr = ['type'=>'Out','lat' => $request->latitude,'lng' => $request->longitude,'user' => $authUser->email];
Log::info($arr);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }
        $clockIn = carbon::parse($clock->clock_in);
        $clockOut = Carbon::parse($request->clock_out);
        $this->validateClockTime($clockIn, $clockOut);
        
        
        if ($clock->location_type == "home") {
            return $this->handleHomeClockOut($clock, $clockOut);
        }
        if ($clock->location_type == "float") {
            $latitudeOut = $request->latitude;
            $longitudeOut = $request->longitude;
            if (!$latitudeOut || !$longitudeOut) {
                return $this->returnError('Latitude and Longitude are required for float clock-out.');
            }

            return $this->handleFloatClockOut($clock, $clockOut, $latitudeOut, $longitudeOut);
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
        $totalIssueCount = ClockInOut::where('is_issue', true)
            ->whereBetween('clock_in', [$startOfMonth, $endOfMonth])
            ->count();
        $response = [
            'clockIssues' => $filtersApplied
                ? IssueResource::collection($clocks)
                : IssueResource::collectionWithPagination($clocks),
            'count' => $totalIssueCount,
        ];

        return $this->returnData('data', $response);


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