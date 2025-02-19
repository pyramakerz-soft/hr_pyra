<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Users\Models\User;
use Modules\Users\Models\WorkType;

/**
 * @OA\Schema(
 *   schema="WorkType",
 *   type="object",
 *   required={"name"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="Home"),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-01T12:00:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-01T12:30:00Z")
 * )
 */
class WorkTypeController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        // $this->middleware("permission:work-type-list")->only(['index', 'show']);
        // $this->middleware("permission:work-type-list")->only(['store']);
        // $this->middleware("permission:work-type-list")->only(['update']);
        // $this->middleware("permission:work-type-list")->only(['destroy']);

    }
    /**
     * @OA\Get(
     *   path="/api/work_types",
     *   summary="Get a list of work types",
     *   tags={"WorkType"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="List of work types",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/WorkType")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No work types found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No WorkTypes Found")
     *     )
     *   )
     * )
     */
    public function index()
    {
        $workTypes = WorkType::all();
        if ($workTypes->isEmpty()) {
            return $this->returnError('No WorkTypes Found');
        }
        return $this->returnData('workTypes', $workTypes, ' WorkTypes Data');
    }

    /**
     * @OA\Post(
     *   path="/api/work_types",
     *   summary="Create a new work type",
     *   tags={"WorkType"},
     *   security={{"bearerAuth": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Remote")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Work type created successfully",
     *     @OA\JsonContent(ref="#/components/schemas/WorkType")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Invalid input",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Validation Error")
     *     )
     *   )
     * )
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'unique:work_types,name'],
        ]);
        $workType = WorkType::create([
            'name' => $request->name,
        ]);
        return $this->returnData('WorkType', $workType, 'Work Type Stored Successfully');
    }
    /**
     * @OA\Get(
     *   path="/api/work_types/{workType}",
     *   summary="Get a specific work type",
     *   tags={"WorkType"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="workType",
     *     in="path",
     *     description="ID of the work type",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Work type details",
     *     @OA\JsonContent(ref="#/components/schemas/WorkType")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Work type not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Work Type Not Found")
     *     )
     *   )
     * )
     */
    public function show(WorkType $workType)
    {
        return $this->returnData('WorkType', $workType, 'Work Type Data');

    }
    /**
     * @OA\Post(
     *   path="/api/work_types/{workType}",
     *   summary="Update an existing work type",
     *   tags={"WorkType"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="workType",
     *     in="path",
     *     description="ID of the work type",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\RequestBody(
     *     required=false,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Hybrid")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Work type updated successfully",
     *     @OA\JsonContent(ref="#/components/schemas/WorkType")
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Invalid input",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Validation Error")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Work type not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Work Type Not Found")
     *     )
     *   )
     * )
     */
    public function update(Request $request, WorkType $workType)
    {
        $this->validate($request, [
            'name' => ['nullable', 'string', Rule::unique('work_types', 'name')->ignore($workType->id)],
        ]);
        $workType->update([
            'name' => $request->name,
        ]);
        return $this->returnData('WorkType', $workType, 'Work Type updated Successfully');

    }

    /**
     * @OA\Delete(
     *   path="/api/work_types/{workType}",
     *   summary="Delete a work type",
     *   tags={"WorkType"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="workType",
     *     in="path",
     *     description="ID of the work type",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Work type deleted successfully",
     *     @OA\JsonContent(ref="#/components/schemas/WorkType")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Work type not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Work Type Not Found")
     *     )
     *   )
     * )
     */
    public function destroy(WorkType $workType)
    {
        $workType->delete();
        return $this->returnData('WorkType', $workType, 'Work Type deleted Successfully');

    }
    /**
     * @OA\Get(
     *     path="/api/employees_workTypes_percentage",
     *     tags={"WorkType"},
     *     summary="Get the percentage of employees by work type (site/home) for a specific year",
     *     security={{"bearerAuth": {}}},
     *     description="Retrieve the percentage of employees assigned to site or home work types for a given year. Defaults to the current year if no year is provided.",
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="The year to filter employee work type data (defaults to the current year if not provided)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=2024
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with the percentage of employees by work type",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="userWorkTypes",
     *                 type="object",
     *                 @OA\Property(property="site", type="number", example=60.5),
     *                 @OA\Property(property="home", type="number", example=39.5)
     *             ),
     *             @OA\Property(property="message", type="string", example="Percentage of employee work types up to the year 2023")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid year provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid year provided")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No employees found for the specified year",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="There are no employees found up to the year 2023")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getEmployeesWorkTypesPercentage(Request $request)
    {
        $data = [
            'site' => 0,
            'home' => 0,
        ];

        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year provided");
        }

        // Define the end of the specified year
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        // Retrieve work type IDs using their names
        $siteWorkTypeId = WorkType::where('name', 'site')->value('id');
        $homeWorkTypeId = WorkType::where('name', 'home')->value('id');

        // Get all work types of employees hired up to the end of the specified year
        $workTypes = User::join('user_details', 'users.id', '=', 'user_details.user_id')
            ->where('user_details.hiring_date', '<=', $endOfYear)
            ->join('user_work_type', 'users.id', '=', 'user_work_type.user_id')
            ->pluck('user_work_type.work_type_id'); //Directly get the work type IDs

        if ($workTypes->isEmpty()) {
            return $this->returnError("There are no employees found up to the year {$year}");
        }

        // Count occurrences of each work type
        $counts = $workTypes->countBy();

        $data['site'] = $counts->get($siteWorkTypeId, 0);
        $data['home'] = $counts->get($homeWorkTypeId, 0);

        // Calculate percentages
        $totalWorkTypes = $data['site'] + $data['home'];
        $percentages = [
            'site' => $totalWorkTypes > 0 ? ($data['site'] / $totalWorkTypes) * 100 : 0,
            'home' => $totalWorkTypes > 0 ? ($data['home'] / $totalWorkTypes) * 100 : 0,
        ];

        return $this->returnData('userWorkTypes', $percentages, 'Percentage of employee work types up to the year ' . $year);
    }
    /**
     * @OA\Get(
     *     path="/api/users/workTypes",
     *     tags={"WorkType"},
     *     summary="Get the work types assigned to each user",
     *     security={{"bearerAuth": {}}},
     *     description="Retrieve the work types assigned to each user.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with user work type data",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="user_work_types",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_work_type", type="object", example={"user_id": 1, "work_type_id": 2})
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="User WorkType Data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
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

}
