<?php

namespace Modules\Clocks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Clocks\Http\Requests\Api\AddClockRequest;
use Modules\Clocks\Http\Requests\Api\ClockInRequest;
use Modules\Clocks\Http\Requests\Api\ClockOutRequest;
use Modules\Clocks\Http\Requests\Api\UpdateClockRequest;
use Modules\Clocks\Models\ClockInOut;
use Modules\Clocks\Resources\Api\ClockResource;
use Modules\Clocks\Resources\Api\IssueResource;
use Modules\Clocks\Traits\ClockCalculationsHelperTrait;
use Modules\Clocks\Traits\ClockInTrait;
use Modules\Clocks\Traits\ClockOutTrait;
use Modules\Clocks\Exports\ClocksExport;
use Modules\Clocks\Filters\Api\DateFilter;
use Modules\Clocks\Filters\Api\DepartmentFilter;
use Modules\Clocks\Filters\Api\MonthFilter;
use Modules\Users\Models\User;

/**
 * @OA\Schema(
 *     schema="ClockInOut",
 *     type="object",
 *     title="Clock In and Out Schema",
 *     description="Schema for clock-in and clock-out records.",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Clock record unique identifier",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="clock_in",
 *         type="string",
 *         format="date-time",
 *         description="Clock-in timestamp",
 *         example="2024-09-25T08:30:00"
 *     ),
 *     @OA\Property(
 *         property="clock_out",
 *         type="string",
 *         format="date-time",
 *         description="Clock-out timestamp",
 *         example="2024-09-25T17:00:00"
 *     ),
 *     @OA\Property(
 *         property="duration",
 *         type="string",
 *         format="time",
 *         description="Duration of the work period",
 *         example="08:30:00"
 *     ),
 *     @OA\Property(
 *         property="location_type",
 *         type="string",
 *         description="Type of location (e.g., on-site, remote)",
 *         example="on-site"
 *     ),
 *     @OA\Property(
 *         property="late_arrive",
 *         type="string",
 *         format="time",
 *         description="Late arrival time",
 *         example="00:10:00"
 *     ),
 *     @OA\Property(
 *         property="early_leave",
 *         type="string",
 *         format="time",
 *         description="Early leave time",
 *         example="00:05:00"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         description="ID of the user who clocked in/out",
 *         example=23
 *     ),
 *     @OA\Property(
 *         property="location_id",
 *         type="integer",
 *         description="ID of the location where clocking took place",
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp when the record was created",
 *         example="2024-09-25T08:30:00"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Timestamp when the record was last updated",
 *         example="2024-09-25T17:00:00"
 *     )
 * )
 */

class ClockController extends Controller
{
    use ResponseTrait, ClockCalculationsHelperTrait,  ClockInTrait, ClockOutTrait;
    
    protected $filters;
    public function __construct(      
    )
    {
        $this->filters = [
            new DepartmentFilter(),
            new DateFilter(),
            new MonthFilter(),
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/all_clocks",
     *     tags={"Clock"},
     *     summary="Get All Clock Records",
     *     description="Retrieve all clock-in and clock-out records with optional filters and pagination.",
     *     operationId="getAllClocks",
     *     @OA\Parameter(
     *         name="department",
     *         in="query",
     *         description="Filter by department ID",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="export",
     *         in="query",
     *         description="Flag to export the clock records",
     *         required=false,
     *         @OA\Schema(
     *             type="boolean",
     *             example=""
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All Clocks Data",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="clocks",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=23),
     *                         @OA\Property(property="clock_in", type="string", format="date-time", example="2024-09-25T08:30:00"),
     *                         @OA\Property(property="clock_out", type="string", format="date-time", example="2024-09-25T17:00:00"),
     *                         @OA\Property(property="total_hours", type="string", example="8:30"),
     *                         @OA\Property(property="department", type="string", example="Sales")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="total_pages", type="integer", example=5),
     *                     @OA\Property(property="per_page", type="integer", example=7),
     *                     @OA\Property(property="total_records", type="integer", example=35)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="All Clocks Data"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No Clocks Found",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No Clocks Found"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 example="error"
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred while retrieving clock records."
     *             )
     *         )
     *     ),
     *     security={{ "bearerAuth": {} }}
     * )
     */

    public function allClocks(Request $request)
    {


        $query = ClockInOut::query();
    // Apply all filters
    foreach ($this->filters as $filter) {
        $query = $filter->apply($query, $request);
    }

        if ($request->has('month')) {
            $monthYear = $request->get('month');

            if (preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
                list($year, $month) = explode('-', $monthYear);

                $query->whereYear('clock_in', $year)
                    ->whereMonth('clock_in', $month);

                $clocks = $query->get();
            } else {
                return $this->returnError('Invalid Month Format. Expected YYYY-MM.');
            }
        }

        // Handle export request
        if ($request->has('export')) {
            $clocksForExport = $query->orderBy('clock_in', 'desc')->get();  // Using `get()` instead of `paginate()` for export
            // Log the number of clocks to be exported (for debugging)
            Log::info(["clocks_count" => $clocksForExport->count()]);


            // Proceed with export
            return (new clocksExport($clocksForExport, $request->get('department')))
                ->download('all_user_clocks.xlsx');
        }
        // Handle pagination
        $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);

        if ($clocks->isEmpty()) {
            return $this->returnError('No Clocks Found');
        }



        // Prepare and return data
        $data = $this->prepareClockData($clocks);
        if (!isset($data['clocks'])) {
            return $this->returnError('No Clocks Found');
        }

        return $this->returnData("data", $data, "All Clocks Data");
    }

    /**
     * @OA\Get(
     *     path="/api/clocks/user/{user}",
     *     operationId="getUserClocksById",
     *     tags={"Clock"},
     *     summary="Get all clock records for a specific user",
     *     description="Retrieves all clock-in and clock-out records for a specific user with pagination and export functionality.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user whose clock records are being retrieved",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="export",
     *         in="query",
     *         required=false,
     *         description="Flag to export the clock records instead of displaying paginated data",
     *         @OA\Schema(
     *             type="boolean",
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Filter parameters to refine the clock records (e.g., by date range, location, etc.)",
     *         @OA\Schema(
     *             type="string",
     *             example="date_from=2024-01-01&date_to=2024-12-31"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved user clock records",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ClockInOut")),
     *             @OA\Property(property="message", type="string", example="Clocks Data for John Doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No clocks found for this user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No Clocks Found For This User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal Server Error")
     *         )
     *     ),
     * )
     */

     public function getUserClocksById(Request $request, User $user)
     {
         // Start the query for the clocks
         $query = ClockInOut::where('user_id', $user->id);
     
         // Apply filters if any
         foreach ($this->filters as $filter) {
             $query = $filter->apply($query, $request);
         }
     
         // If there's an export request, we want to get all data (not paginated)
         if ($request->has('export')) {
             // Get all data without pagination (this ensures all clocks are exported)
             $clocks = $query->orderBy('clock_in', 'desc')->get(); 
     Log::info(  $clocks->count());
             // Return the export file with all data
             return (new ClocksExport($clocks, null, $user->id))
                 ->download('all_user_clocks.xlsx');
         }
     
         // Otherwise, handle pagination
         $clocks = $query->orderBy('clock_in', 'desc')->paginate(7);
     
         // If no clocks are found, return an error message
         if ($clocks->isEmpty()) {
             return $this->returnError('No Clocks Found For This User');
         }
     
         // Prepare and return paginated data
         $data = $this->prepareClockData($clocks);
         return $this->returnData("data", $data, "Clocks Data for {$user->name}");
     }
     
    /**
     * @OA\Get(
     *     path="/api/user_clocks",
     *     operationId="showUserClocks",
     *     tags={"Clock"},
     *     summary="Get clock records of the authenticated user",
     *     description="Retrieves the clock-in and clock-out records for the authenticated user, with optional filtering, pagination, and export capabilities.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Optional filters to apply to the clock records (e.g., date range, location)",
     *         @OA\Schema(type="string")
     *     ),
     * 
     *     @OA\Parameter(
     *         name="export",
     *         in="query",
     *         required=false,
     *         description="Optional export format (e.g., CSV, PDF)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="The page number for pagination",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved clock records for the authenticated user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/ClockInOut")
     *             ),
     *             @OA\Property(property="message", type="string", example="Clocks Data for John Doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No clocks found for the user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No Clocks Found For This User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal Server Error")
     *         )
     *     ),
     * )
     */

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

            return ($this->clocksExport($clocks, $authUser->department->name ?? null))
                ->download('all_user_clocks.xlsx');
        }

        // Prepare and return data
        $data = $this->prepareClockData($clocks);
        return $this->returnData("data", $data, "Clocks Data for {$authUser->name}");
    }
    /**
     * @OA\Get(
     *     path="/api/clock_by_id/{clock}",
     *     operationId="getClockById",
     *     tags={"Clock"},
     *     summary="Get a specific clock record by its ID",
     *     description="Retrieves the details of a specific clock-in and clock-out record by the clock ID.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="clock",
     *         in="path",
     *         required=true,
     *         description="ID of the clock record to retrieve",
     *         @OA\Schema(
     *             type="integer",
     *             example=1
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully retrieved the clock record",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", ref="#/components/schemas/ClockInOut"),
     *             @OA\Property(property="message", type="string", example="Clock Data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Clock record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Clock record not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Internal Server Error")
     *         )
     *     ),
     * )
     */

    public function getClockById(ClockInOut $clock)
    {


        return $this->returnData("clock", new ClockResource($clock), "Clock Data");
    }
    /**
     * @OA\Post(
     *     path="/api/clock_in",
     *     summary="User Clock In",
     *     description="Allows a user to clock in at home or site based on location type. When clocking in at a site, latitude and longitude of the user's location is compared with the site's location.",
     *     operationId="clockIn",
     *     tags={"Clock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"location_type", "clock_in"},
     *             @OA\Property(property="location_type", type="string", enum={"home", "site"}, description="The location type, either 'home' or 'site'"),
     *             @OA\Property(property="clock_in", type="string", format="date-time", example="2024-09-24 08:00:00", description="The clock-in time in 'Y-m-d H:i:s' format"),
     *             @OA\Property(property="location_id", type="integer", example=1, description="Required if location_type is 'site'", nullable=true),
     *             @OA\Property(property="latitude", type="number", format="float", example=31.2403970, description="User's latitude when clocking in at a site", nullable=true),
     *             @OA\Property(property="longitude", type="number", format="float", example=-29.9660127, description="User's longitude when clocking in at a site", nullable=true)
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clock In Done",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clock_in", type="string", example="2024-09-24 08:00:00"),
     *                 @OA\Property(property="location_type", type="string", example="site"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="late_arrive", type="string", example="00:05:00"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.712776),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.005974),
     *             ),
     *             @OA\Property(property="message", type="string", example="Clock In Done"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or user already clocked in",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You have already clocked in."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         ),
     *     )
     * )
     */
    public function clockIn(ClockInRequest $request)
    {

        // Get the authenticated user
        $authUser = Auth::user();
        $user_id = $authUser->id;
        $clock_in = $request->clock_in;

        $arr = ['type' => 'In', 'version' => $request->version, 'lat' => $request->latitude, 'lng' => $request->longitude, 'user' => $authUser->email];
        Log::info($arr);
        if (!$request->version  && ! App::environment('local'))
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
                return response()->json(['message' => 'Your current mobile is different from the original logged-in phone (' . $authUser->mob . ')(' . $request->mob . ')'], 406);
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
    /**
     * @OA\Post(
     *     path="/api/clock_out",
     *     summary="User Clock Out",
     *     description="Allows a user to clock out from either home or site. If clocking out from a site, latitude and longitude are required.",
     *     operationId="clockOut",
     *     tags={"Clock"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"clock_out"},
     *             @OA\Property(property="clock_out", type="string", format="date-time", example="2024-09-24 17:00:00", description="The clock-out time in 'Y-m-d H:i:s' format"),
     *             @OA\Property(property="latitude", type="number", format="float", example=31.2403970, description="User's latitude when clocking out at a site", nullable=true),
     *             @OA\Property(property="longitude", type="number", format="float", example=29.9660127, description="User's longitude when clocking out at a site", nullable=true)
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clock Out Done",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clock_in", type="string", example="2024-09-24 08:00:00"),
     *                 @OA\Property(property="clock_out", type="string", example="2024-09-24 17:00:00"),
     *                 @OA\Property(property="location_type", type="string", example="site"),
     *                 @OA\Property(property="late_arrive", type="string", example="00:05:00"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=40.712776),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-74.005974),
     *                 @OA\Property(property="duration", type="string", example="08:00:00"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Clock Out Done"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or user is not clocked in",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You are not clocked in."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         ),
     *     )
     * )
     */
    public function clockOut(ClockOutRequest $request)
    {
        $authUser = Auth::user();
        $user_id = $authUser->id;
        $clock = $this->getClockInWithoutClockOut($user_id);
        $arr = ['type' => 'Out', 'lat' => $request->latitude, 'lng' => $request->longitude, 'user' => $authUser->email];
        Log::info($arr);
        if (!$clock) {
            return $this->returnError('You are not clocked in.');
        }
        $clockIn = Carbon::parse($clock->clock_in);
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
    /**
     * @OA\Post(
     *     path="/api/update_clock/user/{user}/clock/{clock}",
     *     summary="Update a User's Clock",
     *     description="Allows HR to update an existing clock entry for a specific user. The request body should include updated clock information.",
     *     operationId="updateUserClock",
     *     tags={"Clock"},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="The ID of the user whose clock is being updated",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="clock",
     *         in="path",
     *         required=true,
     *         description="The ID of the clock entry to update",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"clock_in", "clock_out", "location_type"},
     *             @OA\Property(property="clock_in", type="string", format="date-time", example="2024-10-01 08:00", description="The updated clock-in time in 'Y-m-d H:i:s' format"),
     *             @OA\Property(property="clock_out", type="string", format="date-time", example="2024-10-01 17:00", description="The updated clock-out time in 'Y-m-d H:i:s' format"),
     *             @OA\Property(property="location_type", type="string", enum={"home", "site"}, description="The location type, either 'home' or 'site'"),
     *             @OA\Property(property="location_id", type="integer", example=1, description="The location ID if the location_type is 'site'", nullable=true),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clock Updated Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="clock_in", type="string", example="2024-10-01 08:00:00"),
     *                 @OA\Property(property="clock_out", type="string", example="2024-10-01 17:00:00"),
     *                 @OA\Property(property="location_type", type="string", example="site"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="late_arrive", type="string", example="00:05:00"),
     *                 @OA\Property(property="early_leave", type="string", example=null),
     *             ),
     *             @OA\Property(property="message", type="string", example="Clock updated successfully."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Clock or User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="No clocks found for this user."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid input."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         ),
     *     )
     * )
     */
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
    /**
     * @OA\Post(
     *     path="/api/clock_in/user/{user}",
     *     summary="HR Clock In for a User",
     *     description="Allows HR to clock in on behalf of a user, with conditional location fields based on the location type ('site' or 'home').",
     *     operationId="AddClockByHr",
     *     tags={"Clock"},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="The ID of the user HR is clocking in for",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"clock_in", "location_type"},
     *             @OA\Property(property="clock_in", type="string", format="date-time", example="2024-10-01 08:00:00", description="The clock-in time in 'Y-m-d H:i:s' format"),
     *             @OA\Property(property="location_type", type="string", enum={"home", "site"}, description="The location type, either 'home' or 'site'"),
     *             @OA\Property(property="location_id", type="integer", example=1, description="The location ID if clocking in at a site", nullable=true)
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Clock In Done",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="clock", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="clock_in", type="string", example="2024-10-01 08:00:00"),
     *                 @OA\Property(property="location_type", type="string", example="site"),
     *                 @OA\Property(property="location_id", type="integer", example=1),
     *                 @OA\Property(property="late_arrive", type="string", example="00:05:00"),
     *                 @OA\Property(property="early_leave", type="string", example=null),
     *             ),
     *             @OA\Property(property="message", type="string", example="Clock In Done"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error or user has an existing clock-in without clocking out",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="You already have an existing clock-in without clocking out."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         ),
     *     )
     * )
     */
    public function hrClockIn(AddClockRequest $request, User $user)
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

        return $this->returnData('clock', $clock, 'Clock Issue Updated Successfully');    }
}
