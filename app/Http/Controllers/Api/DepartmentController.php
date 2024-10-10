<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\Api\DepartmentResource;
use App\Models\Department;
use App\Models\User;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *   schema="Department",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="name", type="string", example="HR Department"),
 *   @OA\Property(property="is_location_time", type="boolean", example=true),
 *   @OA\Property(property="manager_id", type="integer", example=2),
 * )
 */
class DepartmentController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        // $this->middleware("permission:department-list")->only(['index', 'show']);
        // $this->middleware("permission:department-create")->only(['store']);
        // $this->middleware("permission:department-edit")->only(['update']);
        // $this->middleware("permission:department-delete")->only(['destroy']);

    }
    /**
     * @OA\Get(
     *   path="/api/departments",
     *   summary="Get a list of departments",
     *   tags={"Department"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="A list of departments",
     *     @OA\JsonContent(
     *       @OA\Property(property="departments", type="array",
     *         @OA\Items(ref="#/components/schemas/Department")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No departments found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No departments found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function index()
    {
        $departments = Department::with('manager')->get();
        if ($departments->isEmpty()) {
            return $this->returnError('No departments Found');
        }
        $data['departments'] = DepartmentResource::collection($departments);
        return $this->returnData("data", $data, "departments Data");

    }
    /**
     * @OA\Post(
     *   path="/api/departments",
     *   summary="Create a new department",
     *   tags={"Department"},
     *   security={{"bearerAuth": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="HR Department"),
     *       @OA\Property(property="is_location_time", type="integer", example=0),
     *       @OA\Property(property="manager_id", type="integer", example=1)
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Department created successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="department stored successfully"),
     *       @OA\Property(property="department", ref="#/components/schemas/Department")
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Invalid input data")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function store(StoreDepartmentRequest $request)
    {
        $department = new Department();
        $department->name = $request->name;
        $department->is_location_time = $request->is_location_time ?? 0;
        $department->manager_id = $request->manager_id;

        if ($department->save()) {
            return $this->returnData("department", $department, "department stored successfully");
        }

    }

    /**
     * @OA\Get(
     *   path="/api/departments/{department}",
     *   summary="Get a department by ID",
     *   tags={"Department"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="department",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer"),
     *     description="Department ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Department data",
     *     @OA\JsonContent(
     *       @OA\Property(property="department", ref="#/components/schemas/Department")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Department not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Department not found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function show(Department $department)
    {
        return $this->returnData("department", new DepartmentResource($department), "department Data");
    }
    /**
     * @OA\Post(
     *   path="/api/departments/{department}",
     *   summary="Update a department",
     *   tags={"Department"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="department",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer"),
     *     description="Department ID"
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", example="Updated Department Name"),
     *       @OA\Property(property="is_location_time", type="integer", example=1),
     *       @OA\Property(property="manager_id", type="integer", example=2)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Department updated successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="department updated successfully"),
     *       @OA\Property(property="department", ref="#/components/schemas/Department")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Department not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Department not found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->name = $request->name ?? $department->name;
        $department->is_location_time = $request->is_location_time ?? $department->is_location_time;

        $department->manager_id = $request->manager_id ?? $department->manager_id;
        if ($department->save()) {
            return $this->returnData("department", $department, "department updated successfully");
        }

    }

    /**
     * @OA\Delete(
     *   path="/api/departments/{department}",
     *   summary="Delete a department",
     *   tags={"Department"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="department",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer"),
     *     description="Department ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Department deleted successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="department deleted successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Department not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Department not found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function destroy(Department $department)
    {
        if ($department->delete()) {
            return $this->returnData("department", $department, "department deleted successfully");
        }

    }
    /**
     * @OA\Get(
     *     path="/api/department_employees",
     *     tags={"Department"},
     *     summary="Get the count of employees per department for a specific year",
     *     security={{"bearerAuth": {}}},
     *     description="Retrieve the number of employees in each department up to a given year, or the current year if no year is provided.",
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="The year to filter employee hiring date (defaults to the current year if not provided)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=2024
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with employee count per department",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="departmentEmployeesCounts",
     *                 type="object",
     *                 example={
     *                     "software": 15,
     *                     "academic": 10,
     *                     "graphic": 20
     *                 }
     *             ),
     *             @OA\Property(property="message", type="string", example="Count of Employee Departments for the year 2023")
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
     *         description="No employees found for the given year",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="There are no employees found up to the year 2025")
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
    public function getDepartmentEmployees(Request $request)
    {
        $data = [];

        // Get all department names and initialize their employee count to 0
        $departments = Department::pluck('name')->toArray();
        foreach ($departments as $department) {
            $data[$department] = 0;
        }

        // Initialize the count for users with no department
        $data['No Department'] = 0;

        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!$year || !preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year provided");
        }

        // Get the current year
        $currentYear = date('Y');

        // If the provided year is greater than the current year, return zero counts
        if ($year > $currentYear) {
            return $this->returnData('departmentEmployeesCounts', $data, 'Count of Employee Departments for future year ' . $year);
        }

        // Define the end of the specified year
        $endOfYear = Carbon::create($year, 12, 31)->endOfDay();

        // Filter employees based on hiring_date up to the end of the specified year
        $employees = User::join('user_details', 'users.id', '=', 'user_details.user_id')
            ->where('user_details.hiring_date', '<=', $endOfYear)
            ->get();
        // If no employees are found up to the specified year, return an error
        if ($employees->isEmpty()) {
            return $this->returnError("There are no employees found up to the year {$year}");
        }

        // Count employees for each department
        foreach ($employees as $employee) {
            $departmentName = Department::find($employee->department_id)->name ?? 'No Department';
            if (array_key_exists($departmentName, $data)) {
                $data[$departmentName]++;
            }
        }

        return $this->returnData('departmentEmployeesCounts', $data, 'Count of Employee Departments for the year ' . $year);
    }

}
