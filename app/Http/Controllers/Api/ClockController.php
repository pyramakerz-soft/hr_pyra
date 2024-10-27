<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddClockRequest;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Http\Requests\Api\UpdateClockRequest;
use App\Models\ClockInOut;
use App\Models\User;
use App\Services\Api\Clock\ClockService;
use App\Traits\ClockTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Lcobucci\Clock\Clock;

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
    use ResponseTrait, ClockTrait;
    protected $clockService;
    public function __construct(ClockService $clockService)
    {
        $this->clockService = $clockService;
        // $this->middleware('permission:clock-list')->only(['allClocks', 'getUserClocksById', 'getClockById']);
        // $this->middleware('permission:clock-create')->only(['hrClockIn']);
        // $this->middleware('permission:clock-edit')->only(['updateUserClock']);

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

        return $this->clockService->getAllClocks($request);

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
     *             example=false
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="filter",
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

        return $this->clockService->getUserClocksById($request, $user);

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
     *         name="filter",
     *         in="query",
     *         required=false,
     *         description="Optional filters to apply to the clock records (e.g., date range, location)",
     *         @OA\Schema(type="string")
     *     ),
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
        return $this->clockService->showUserClocks($request);
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
        return $this->clockService->getClockById($clock);
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
        return $this->clockService->clockIn($request);
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

        return $this->clockService->clockOut($request);

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

        return $this->clockService->updateUserClock($request, $user, $clock);

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
        return $this->clockService->AddClockByHr($request, $user);

    }
    public function getClockIssues(Request $request)
    {
        return $this->clockService->getClockIssues($request);
    }
    public function updateClockIssues(Request $request, ClockInOut $clock)
    {
        return $this->clockService->updateClockIssues($request, $clock);
    }

}
