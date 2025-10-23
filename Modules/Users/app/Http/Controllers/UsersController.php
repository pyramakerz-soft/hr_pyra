<?php


namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Services\Api\User\UserDetailService;
use App\Services\Api\User\UserService;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Clocks\Exports\UserClocksExport;
use Modules\Clocks\Exports\UsersClocksMultiSheetExport;
use Modules\Location\Models\Location;
use Modules\Users\Exports\UsersExport;
use Modules\Users\Http\Requests\Api\User\StoreUserRequest;
use Modules\Users\Http\Requests\Api\User\UpdateUserRequest;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\User;
use Modules\Users\Models\UserDetail;
use Modules\Users\Models\WorkType;
use Modules\Users\Resources\UserDetailResource;
use Modules\Users\Resources\UserResource;
use Modules\Users\Traits\UserTrait;
use Spatie\Permission\Models\Role;

// use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string"),
 *     @OA\Property(property="password", type="string"),

 *     @OA\Property(property="national_id", type="string"),
 *     @OA\Property(property="code", type="string"),
 *     @OA\Property(property="gender", type="string"),
 *     @OA\Property(property="image", type="string"),
 *     @OA\Property(property="serial_number", type="string"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="contact_phone", type="string"),
 *     @OA\Property(property="department_id", type="integer"),

 * )
 */



/**
 * @OA\Schema(
 *   schema="UserDetail",
 *   type="object",
 *   required={"salary", "hourly_rate", "overtime_hourly_rate", "working_hours_day", "overtime_hours", "start_time", "end_time", "emp_type", "hiring_date", "user_id"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="salary", type="number", format="float", example=60000.00),
 *   @OA\Property(property="hourly_rate", type="number", format="float", example=25.00),
 *   @OA\Property(property="overtime_hourly_rate", type="number", format="float", example=37.50),
 *   @OA\Property(property="working_hours_day", type="number", format="float", example=8.00),
 *   @OA\Property(property="overtime_hours", type="number", format="float", example=2.00),
 *   @OA\Property(property="start_time", type="string", format="time", example="08:00:00"),
 *   @OA\Property(property="end_time", type="string", format="time", example="17:00:00"),
 *   @OA\Property(property="emp_type", type="string", example="full-time"),
 *   @OA\Property(property="hiring_date", type="string", format="date", example="2024-01-01"),
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-01T12:00:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-01T12:30:00Z")
 * )
 */

class UsersController extends Controller
{
    use ResponseTrait, UserTrait;


    public function __construct() {}


    /**
     * @OA\Get(
     *     path="/api/users/getAllUsers",
     *     tags={"User"},
     *     summary="Get all users",
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             example=""
     *         ),
     *         description="Optional search filter to find users by name or code"
     *     ),
     * 
     * 
     *         @OA\Parameter(
     *         name="export",
     *         in="query",
     *         description="Flag to export the clock records",
     *         required=false,
     *         @OA\Schema(
     *             type="boolean",
     *             example=""
     *         )
     *     ),
     *     @OA\Response(response=200, description="List of users"),
     *     @OA\Response(response=404, description="No users found")
     * )
     */
    public function index(Request $request)
{
    $search = $request->get('search');
    $department = $request->get('department_id');
    $subDepartment = $request->get('sub_department_id');
    $subDepartmentIdsInput = $request->input('sub_department_ids');
    $parsedSubDepartmentIds = collect();

    if (is_array($subDepartmentIdsInput)) {
        $parsedSubDepartmentIds = collect($subDepartmentIdsInput);
    } elseif (is_string($subDepartmentIdsInput)) {
        $parsedSubDepartmentIds = collect(explode(',', $subDepartmentIdsInput));
    }

    if ($subDepartment !== null && $subDepartment !== '') {
        $parsedSubDepartmentIds->push($subDepartment);
    }

    $parsedSubDepartmentIds = $parsedSubDepartmentIds
        ->map(function ($id) {
            return is_numeric($id) ? (int) $id : null;
        })
        ->filter(function ($id) {
            return $id !== null;
        })
        ->unique()
        ->values();
    $from_day = $request->get('from_day');
    $to_day = $request->get('to_day');
    $isAllDepartmentsValue = $department === 'all';
    $isNoDepartmentValue = $department === 'none';
    $allDepartments = $isAllDepartmentsValue || filter_var($request->get('all_departments'), FILTER_VALIDATE_BOOLEAN);
    $usersData = null;

    // Base query
    $usersQuery = User::query();

    // Filter by department
    $hasDepartmentFilter = !is_null($department) && $department !== '' && ! $isAllDepartmentsValue && ! $isNoDepartmentValue;
    if ($isNoDepartmentValue) {
        $usersQuery->whereNull('department_id');
    } elseif ($hasDepartmentFilter) {
        $usersQuery->where('department_id', $department);
    }

    // Filter by sub-department
    $hasSubDepartmentFilter = $parsedSubDepartmentIds->isNotEmpty() && ! $isNoDepartmentValue;
    if ($hasSubDepartmentFilter) {
        $usersQuery->whereIn('sub_department_id', $parsedSubDepartmentIds->all());
    }

    if ($from_day && $to_day) {
        $fromDate = Carbon::parse($from_day)->startOfDay();
        $toDate   = Carbon::parse($to_day)->endOfDay();

        $usersQuery->whereHas('user_clocks', function($query) use ($fromDate, $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        });
    }
    // Handle export
    if ($request->has('export')) {
        $users = $usersQuery->get();

        return (new UsersClocksMultiSheetExport($users,$from_day, $to_day))
            ->download('all_user_clocks.xlsx');
    }
    

    // Handle search
    if ($search) {
        $users = $this->searchUsersByNameOrCode($search);

        // Apply department/sub-department filtering to search results
        if ($isNoDepartmentValue) {
            $users = $users->filter(function ($user) {
                return $user->department_id === null;
            });
        } elseif ($hasDepartmentFilter) {
            $users = $users->where('department_id', $department);
        }
        if ($hasSubDepartmentFilter) {
            $users = $users->whereIn('sub_department_id', $parsedSubDepartmentIds->all());
        }

        $usersData = $users->isEmpty() ? null : [
            'users' => UserResource::collection($users),
            'pagination' => null,
        ];
    } else {
        // If filters are applied, fetch all users matching the filters (no pagination)
        // Remove pagination if filtering by date, department, sub-department, or requesting all departments
        $shouldReturnAllUsers = $allDepartments || $hasDepartmentFilter || $isNoDepartmentValue || $hasSubDepartmentFilter || ($from_day && $to_day);
        if ($shouldReturnAllUsers) {
            $users = $usersQuery->get();
            $usersData = [
                // Pass dates to UserResource!
                'users' => UserResource::collection($users)->additional([
                    'from_day' => $from_day,
                    'to_day' => $to_day,
                ]),
                'pagination' => null,
            ];
        } else {
            $users = $usersQuery->paginate(5);
            $usersData = [
                'users' => UserResource::collection($users),
                'pagination' => $this->formatPagination($users),
            ];
        }

    }

    if (!$usersData) {
        return $this->returnError('No Users Found');
    }

    return $this->returnData("data", $usersData, "Users Data");
}
public function exportClocks(Request $request)
{
    $userIds = $request->input('user_ids', []);
    $from_day = $request->input('from_day');
    $to_day = $request->input('to_day');

    if (empty($userIds)) {
        return response()->json(['error' => 'No users selected.'], 400);
    }

    $users = User::with([
        'user_clocks', 'department', 'timezone', 'excuses', 'overTimes', 'user_vacations'
    ])->whereIn('id', $userIds)->get();

    return (new UsersClocksMultiSheetExport($users, $from_day, $to_day))
        ->download('all_user_clocks.xlsx');
}

public function exportAttendance(Request $request)
{
    $userIds = $request->input('user_ids', []);
    $from_day = $request->input('from_day');
    $to_day = $request->input('to_day');

    $usersQuery = User::query()->whereIn('id', $userIds);

    // Filter attendances by date
    if ($from_day && $to_day) {
        $usersQuery->whereHas('user_clocks', function($query) use ($from_day, $to_day) {
            $query->whereBetween('created_at', [$from_day, $to_day]);
        });
    }

    $users = $usersQuery->get();

    return (new UsersClocksMultiSheetExport($users, $from_day, $to_day))->download('all_user_clocks.xlsx');
}

// Function to format pagination data
private function formatPagination($users)
{
    return [
        'current_page' => $users->currentPage(),
        'last_page' => $users->lastPage(),
        'per_page' => $users->perPage(),
        'total' => $users->total(),
    ];
}

    



    /**
     * @OA\Get(
     *     path="/api/users/manager_names",
     *     tags={"User"},
     *     summary="Get names of all managers",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of manager names retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="managerNames", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="manager_id", type="integer", description="ID of the manager"),
     *                     @OA\Property(property="manager_name", type="string", description="Name of the manager")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", description="Success message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No managers found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No managers found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function ManagerNames()
    {
        $data = [];
        $role = Role::where('name', 'Manager')->first();
        if (!$role) {
            return $this->returnError('Manager role not found', 404);
        }
        $managers = User::Role('manager')->get(['id', 'name']);
        $data = $managers->map(function ($manager) {
            return [
                'manager_id' => $manager->id,
                'manager_name' => $manager->name,
            ];
        });
        return $this->returnData('managerNames', $data, 'manager names');
    }







    /**
     * @OA\Get(
     *     path="/api/users/team_lead_names",
     *     tags={"User"},
     *     summary="Get names of all team_leads",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of team_lead names retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="team_lead_Names", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="team_lead_id", type="integer", description="ID of the team_lead"),
     *                     @OA\Property(property="team_lead_name", type="string", description="Name of the team_lead")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", description="Success message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No team_lead found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No team_lead found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function teamleadNames()
    {
        $data = [];
        $role = Role::where('name', 'Team leader')->first();
        if (!$role) {
            return $this->returnError('team lead role not found', 404);
        }
        $teamLeads = User::Role('Team leader')->get(['id', 'name']);
        $data = $teamLeads->map(function ($teamLead) {
            return [
                'team_lead_id' => $teamLead->id,
                'team_lead_name' => $teamLead->name,
            ];
        });
        return $this->returnData('teamLeadNames', $data, 'Team leader names');
    }




    /**
     * @OA\Post(
     *     path="/api/users/create_user",
     *     tags={"User"},
     *     summary="Create a new user",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "phone", "department_id"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="code", type="string", example="Soft-hr"),

     *             @OA\Property(property="email", type="string", example="john@test.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="phone", type="string", example="01203376559"),
     *             @OA\Property(property="contact_phone", type="string", example="01203376669"),
     *             @OA\Property(property="national_id", type="string", example="30201010214335"),
     *             @OA\Property(property="gender", type="string", example="m"),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="sub_department_id", type="integer", example=1),

     *             @OA\Property(property="image", type="string", format="binary", description="Profile image"),
     *             @OA\Property(property="role", type="array", @OA\Items(type="string"), description="Roles to assign",example="employee"),
     *             @OA\Property(property="location_id", type="array", @OA\Items(type="integer"), description="Location IDs to assign",example=1),
     *             @OA\Property(property="work_type_id", type="array", @OA\Items(type="integer"), description="Work Type IDs to assign",example=1),
     *             @OA\Property(property="salary", type="number", format="float", description="User salary",example="8000"),
     *             @OA\Property(property="working_hours_day", type="number", format="float", description="Working hours per day",example=8),
     *             @OA\Property(property="overtime_hours", type="number", format="float", description="Overtime hours worked",example=1.50),
     *             @OA\Property(property="start_time", type="string", format="time", description="Start time of work",example="07:00"),
     *             @OA\Property(property="end_time", type="string", format="time", description="End time of work",example="15:00"),
     *             @OA\Property(property="emp_type", type="string", description="Job title",example="Graphic Designer"),
     *             @OA\Property(property="hiring_date", type="string", format="date", description="Hiring date",example="2024-09-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", description="User ID"),

     *                     @OA\Property(property="name", type="string", description="User name"),
     *                     @OA\Property(property="email", type="string", description="User email"),
     *                     @OA\Property(property="phone", type="string", description="User phone"),
     *                     @OA\Property(property="contact_phone", type="string", description="User contact phone"),
     *                     @OA\Property(property="national_id", type="string", description="User national ID"),
     *                     @OA\Property(property="gender", type="string", description="User gender"),
     *                     @OA\Property(property="department_id", type="integer", description="Department ID"),
     *                     @OA\Property(property="image", type="string", description="URL of user image"),
     *                     @OA\Property(property="serial_number", type="string", description="User serial number")
     *                 ),
     *                 @OA\Property(property="user_detail", type="object",
     *                     @OA\Property(property="salary", type="number", format="float", description="User salary"),
     *                     @OA\Property(property="working_hours_day", type="number", format="float", description="Working hours per day"),
     *                     @OA\Property(property="hourly_rate", type="number", format="float", description="User hourly rate"),
     *                     @OA\Property(property="overtime_hourly_rate", type="number", format="float", description="Overtime hourly rate"),
     *                     @OA\Property(property="overtime_hours", type="number", format="float", description="Overtime hours worked"),
     *                     @OA\Property(property="start_time", type="string", format="time", description="Start time of work"),
     *                     @OA\Property(property="end_time", type="string", format="time", description="End time of work"),
     *                     @OA\Property(property="emp_type", type="string", description="Employment type"),
     *                     @OA\Property(property="hiring_date", type="string", format="date", description="Hiring date")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="User Created")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to Store User")
     *         )
     *     )
     * )
     */
    public function store(StoreUserRequest $request)
    {

        $request->validated();

        if ($request['department_id']) {
            $department = Department::find($request['department_id']);
            if (!$department) {
                return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
            }
        }
        if ($request->sub_department_id) {
            $subDepartment = SubDepartment::find($request['sub_department_id']);

            if (($department && $subDepartment->department_id != $department->id) || (! $subDepartment)) {
                return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
            }
        }


        $code =  $request->code;

        // Handle image upload
        $imageUrl = null;
        if (request()->hasFile('image')) {
            $image = request()->file('image');
            $imageUrl = $this->uploadImage($image);
        }
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => Hash::make($request['password']),
            'phone' => $request['phone'],
            'contact_phone' => $request['contact_phone'],
            'national_id' => $request['national_id'],
            'code' => $code,
            'gender' => $request['gender'],
            'department_id' => $request['department_id'],

            'sub_department_id' => $request['sub_department_id'],
            'timezone_id' => $request['timezone_id'],

            'is_part_time' => (bool) ($request['is_part_time'] ?? false),

            'image' => $imageUrl,
            'serial_number' => null,
        ]);


        if (!$user) {
            return $this->returnError('Failed to Store User');
        }




        $salary = $request['salary'];
        $working_hours_day = $request['working_hours_day'];
        $overtime_hours = $request['overtime_hours'];
        $hourly_rate = ($salary / 22) / $working_hours_day;
        $overtime_hourly_rate = (($salary / 30) / $working_hours_day) * $overtime_hours;
        $start_time = $request['start_time'];
        $end_time = $request['end_time'];

        if (Carbon::parse($end_time)->lessThanOrEqualTo(Carbon::parse($start_time))) {
            return $this->returnError('End time must be later than start time', 422); // Return 422 Unprocessable Entity
        }

        $userDetail = UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hourly_rate' => $overtime_hourly_rate,
            'overtime_hours' => $overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $request['emp_type'],
            'hiring_date' => $request['hiring_date'],
            'user_id' => $user->id,
            // 'is_float' => $data['is_float'],

        ]);


        $this->assignRoles($user, [$request['role']]);

        $this->assignLocations($user, $request['location_id'] ?? []);
        $this->assignWorkTypes($user, $request['work_type_id'] ?? []);

        $data = [
            'user' => $user,
            'user_detail' => $userDetail,
        ];

        return $this->returnData("data", $data, "User Created");
    }



    /**
     * @OA\Get(
     *     path="/api/users/get_user_by_id/{user}",
     *     tags={"User"},
     *     summary="Get user by ID",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the user to retrieve",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="User", type="object",
     *                 @OA\Property(property="id", type="integer", description="User ID"),
     *                 @OA\Property(property="name", type="string", description="User name"),
     *                 @OA\Property(property="email", type="string", description="User email"),
     *                 @OA\Property(property="phone", type="string", description="User phone"),
     *                 @OA\Property(property="contact_phone", type="string", description="User contact phone"),
     *                 @OA\Property(property="national_id", type="string", description="User national ID"),
     *                 @OA\Property(property="gender", type="string", description="User gender"),
     *                 @OA\Property(property="department_id", type="integer", description="Department ID"),
     *                 @OA\Property(property="image", type="string", description="URL of user image"),
     *                 @OA\Property(property="serial_number", type="string", description="User serial number"),
     *             ),
     *             @OA\Property(property="message", type="string", description="Success message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized access")
     *         )
     *     )
     * )
     */
    public function show(User $user)
    {

        $userDetail = UserDetail::where('user_id', $user->id)->first();
        return $this->returnData("User", new UserDetailResource($userDetail), "User Data");
    }
    /**
     * @OA\Post(
     *     path="/api/users/update_user/{user}",
     *     tags={"User"},
     *     summary="Update an existing user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the user to be updated",
     *         example=1
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="code", type="string", example="Soft-hr"),
     *             @OA\Property(property="email", type="string", example="john@test.com"),
     *             @OA\Property(property="phone", type="string", example="01203376559"),
     *             @OA\Property(property="contact_phone", type="string", example="01203376669"),
     *             @OA\Property(property="national_id", type="string", example="30201010214335"),
     *             @OA\Property(property="gender", type="string", example="m"),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="image", type="string", format="binary", description="Profile image"),
     *             @OA\Property(property="salary", type="number", format="float", description="User salary", example=8000),
     *             @OA\Property(property="working_hours_day", type="number", format="float", description="Working hours per day", example=8.00),
     *             @OA\Property(property="overtime_hours", type="number", format="float", description="Overtime hours worked", example=1.50),
     *             @OA\Property(property="start_time", type="string", format="time", description="Start time of work", example="07:00:00"),
     *             @OA\Property(property="end_time", type="string", format="time", description="End time of work", example="15:00:00"),
     *             @OA\Property(property="emp_type", type="string", description="Job title", example="Graphic Designer"),
     *             @OA\Property(property="hiring_date", type="string", format="date", description="Hiring date", example="2024-09-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", description="User ID"),
     *                     @OA\Property(property="code", type="string", description="User code"),

     *                     @OA\Property(property="name", type="string", description="User name"),
     *                     @OA\Property(property="email", type="string", description="User email"),
     *                     @OA\Property(property="phone", type="string", description="User phone"),
     *                     @OA\Property(property="contact_phone", type="string", description="User contact phone"),
     *                     @OA\Property(property="national_id", type="string", description="User national ID"),
     *                     @OA\Property(property="gender", type="string", description="User gender"),
     *                     @OA\Property(property="department_id", type="integer", description="Department ID"),
     *                     @OA\Property(property="sub_department_id", type="integer", description="sub_department_id ID"),

     *                     @OA\Property(property="image", type="string", description="URL of user image"),
     *                     @OA\Property(property="serial_number", type="string", description="User serial number")
     *                 ),
     *                 @OA\Property(property="user_detail", type="object",
     *                     @OA\Property(property="salary", type="number", format="float", description="User salary"),
     *                     @OA\Property(property="working_hours_day", type="number", format="float", description="Working hours per day"),
     *                     @OA\Property(property="hourly_rate", type="number", format="float", description="User hourly rate"),
     *                     @OA\Property(property="overtime_hourly_rate", type="number", format="float", description="Overtime hourly rate"),
     *                     @OA\Property(property="overtime_hours", type="number", format="float", description="Overtime hours worked"),
     *                     @OA\Property(property="start_time", type="string", format="time", description="Start time of work"),
     *                     @OA\Property(property="end_time", type="string", format="time", description="End time of work"),
     *                     @OA\Property(property="emp_type", type="string", description="Employment type"),
     *                     @OA\Property(property="hiring_date", type="string", format="date", description="Hiring date")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="User Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to Update User")
     *         )
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, User $user)
    {

        $request->validated();


        if ($request['department_id']) {
            $department = Department::find($request['department_id']);
            if (!$department) {
                return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
            }
        
        if ($request->sub_department_id) {
            $subDepartment = SubDepartment::find($request['sub_department_id']);

            if (($department && $subDepartment->department_id != $department->id) || (! $subDepartment)) {
                return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
            }
        }

    }
        // Ensure that the department_id exists in $data before accessing it
        $departmentId = isset($request['department_id']) ? $request['department_id'] : $user->department_id;

        $sub_department_id = isset($request['sub_department_id']) ? $request['sub_department_id'] : $user->sub_department_id;





        // Check if an image is provided in the request
        if (isset($request['image']) && $request['image']->isValid()) {
            $imageUrl = $this->uploadImage($request['image']);
        } else {
            $imageUrl = $user->image;
        }

        // Update user information
        $updatedUser =    $user->update([
            'code' => $request['code'] ?? $user->code,
            'name' => $request['name'] ?? $user->name,
            'email' => $request['email'] ?? $user->email,
            'phone' => $request['phone'] ?? $user->phone,
            'contact_phone' => $request['contact_phone'] ?? $user->contact_phone,
            'national_id' => $request['national_id'] ?? $user->national_id,
            'gender' => $request['gender'] ?? $user->gender,
            'department_id' => $departmentId,
            'sub_department_id' => $sub_department_id,
            'timezone_id' => $request['timezone_id'] ?? $user->timezone_id,

            'is_part_time' => $request->has('is_part_time') ? (bool) $request['is_part_time'] : $user->is_part_time,
            'image' => $imageUrl,
        ]);


        if (!$updatedUser) {
            return $this->returnError('Failed to Update User');
        }

        $userDetail = UserDetail::where('user_id', $user->id)->first();
        if (!$userDetail) {
            return $this->returnError('No User Detail Found for this User');
        }
        // Update user detail



        $salary = $request['salary'] ?? $userDetail->salary;
        $working_hours_day = $request['working_hours_day'] ?? $userDetail->working_hours_day;
        $hourly_rate = $working_hours_day === null || $working_hours_day == 0 ? 0 : ($salary / 30) / $working_hours_day;

        $start_time = isset($request['start_time']) ? Carbon::parse($request['start_time'])->format("H:i:s") : $userDetail->start_time;
        $end_time = isset($request['end_time']) ? Carbon::parse($request['end_time'])->format("H:i:s") : $userDetail->end_time;

        // Use Carbon's diffInSeconds for time comparison to avoid format issues
        if (Carbon::parse($end_time)->lessThanOrEqualTo(Carbon::parse($start_time))) {
            return $this->returnError('End time must be later than start time', 422); // Return 422 Unprocessable Entity
        }
        $updatedUserDetail =  $userDetail->update([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => $request['overtime_hours'] ?? $userDetail->overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $request['emp_type'] ?? $userDetail->emp_type,
            'hiring_date' => $request['hiring_date'] ?? $userDetail->hiring_date,
            'user_id' => $userDetail->user_id,
            // 'is_float' => $request['is_float'] ,
        ]);


        // Assign roles, locations, and work types

            Log::info($request);
            Log::info(json_encode([$request['role']]));
            $this->assignRoles($user, [$request['role']]);
        

        if ($request->has('location_id')) {
            $this->assignLocations($user, $request->input('location_id'));
        }

        if ($request->has('work_type_id')) {
            $this->assignWorkTypes($user, $request->input('work_type_id'));
        }

        $data = [
            'user' => $user->fresh(), // Fetch the updated user data
            'user_detail' => $userDetail->fresh(), // Fetch the updated user details
        ];

        return $this->returnData("data", $data, "User Updated");
    }
    /**
     * @OA\Delete(
     *     path="/api/users/delete_user/{user}",
     *     tags={"User"},
     *     summary="Delete a user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID of the user to be deleted",
     *         example=1
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="User ID"),
     *                 @OA\Property(property="name", type="string", description="User name"),
     *                 @OA\Property(property="email", type="string", description="User email"),
     *                 @OA\Property(property="phone", type="string", description="User phone"),
     *                 @OA\Property(property="contact_phone", type="string", description="User contact phone"),
     *                 @OA\Property(property="national_id", type="string", description="User national ID"),
     *                 @OA\Property(property="gender", type="string", description="User gender"),
     *                 @OA\Property(property="department_id", type="integer", description="Department ID"),
     *                 @OA\Property(property="image", type="string", description="URL of user image")
     *             ),
     *             @OA\Property(property="message", type="string", example="User deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->returnData("user", $user, "user deleted");
    }
    /**
     * @OA\Get(
     *     path="/api/users/users_by_name",
     *     tags={"User"},
     *     summary="Get all user names",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user names retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="string", description="User name")
     *             ),
     *             @OA\Property(property="message", type="string", example="UsersName")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Server Error")
     *         )
     *     )
     * )
     */
    public function getAllUsersNames()
    {
        $usersByName = User::pluck('name');
        return $this->returnData("usersNames", $usersByName, "UsersName");
    }
    public function updatePassword(Request $request, User $user)
    {

        $request->validate([
            'password' => ['required', 'min:6'],
        ]);
        $user->update([
            'password' => bcrypt($request->password) ?? $user->password,
        ]);
        return $this->returnSuccessMessage("Password Updated Successfully");
    }
    /**
     * @OA\Get(
     *     path="/api/users/employees_per_month",
     *     tags={"User"},
     *     summary="Get the employee count per month for a specific year",
     *     security={{"bearerAuth": {}}},
     *     description="Retrieve the number of employees hired by month for a given year. Defaults to the current year if no year is provided.",
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="The year to filter employee hiring data (defaults to the current year if not provided)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example=2023
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response with the employee count per month",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="employeeCount",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="employee_count", type="integer", example=25),
     *                     @OA\Property(property="custom_month", type="string", example="2023-Feb")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Employees count up to the year 2023")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid year provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid year parameter")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No employees found for the specified year",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No employees found up to the year 2023")
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
    public function employeesPerMonth(Request $request)
    {
        // Check if 'year' is provided in the request, otherwise default to current year
        $year = $request->has('year') ? $request->input('year') : date('Y');

        // Validate the year input if provided in the request
        if (!preg_match('/^\d{4}$/', $year)) {
            return $this->returnError("Invalid year parameter");
        }

        // Get the current year and date
        $currentYear = date('Y');
        $currentDate = Carbon::now();

        // Initialize the employee counts
        $employeeCounts = collect();
        $cumulativeCount = 0;

        // Handle future years by returning 0 for all months
        if ($year > $currentYear) {
            for ($month = 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
            }
            return $this->returnData('employeeCount', $employeeCounts->values()->all(), 'Employees count for future year ' . $year);
        }

        // Loop through each month of the year
        for ($month = 1; $month <= 12; $month++) {
            // Set the start and end of the month
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            // If the month is after the current date, set the count to 0 and continue
            if ($startOfMonth->isAfter($currentDate)) {
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
                continue;
            }

            $customMonth = $startOfMonth->format('Y-M');

            // Count employees hired up to and including the end of the current month
            $employeeCount = User::whereHas('user_detail', function ($query) use ($endOfMonth) {
                $query->where('hiring_date', '<=', $endOfMonth);
            })->count();

            $cumulativeCount = $employeeCount;

            $employeeCounts[$customMonth] = [
                'employee_count' => $cumulativeCount,
                'custom_month' => $customMonth,
            ];
        }

        // Reset count for future months within the current year
        if ($year == $currentYear) {
            for ($month = $currentDate->month + 1; $month <= 12; $month++) {
                $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
                $employeeCounts[$startOfMonth->format('Y-M')] = [
                    'employee_count' => 0,
                    'custom_month' => $startOfMonth->format('Y-M'),
                ];
            }
        }

        // Sort the employee counts by month
        $formattedCounts = $employeeCounts->sortBy(function ($value, $key) {
            return Carbon::parse($key)->month;
        });

        return $this->returnData('employeeCount', $formattedCounts->values()->all(), 'Employees count up to the year ' . $year);
    }




    /**
     * @OA\Get(
     *   path="/api/users/user_details",
     *   summary="Get a list of user details",
     *   tags={"User"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="List of user details",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/UserDetail")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No user details found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No User Details Found")
     *     )
     *   )
     * )
     */
    public function allUserDetails()
    {
        $user_details = UserDetail::all();
        if ($user_details->isEmpty()) {
            return $this->returnError('No User Details Found');
        }
        $data['user_details'] = UserDetailResource::collection($user_details);
        return $this->returnData('data', $data, 'User Details Data');
    }

    /**
     * @OA\Get(
     *   path="/api/users/user_details/{userDetail}",
     *   summary="Get a specific user detail",
     *   tags={"User"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="userDetail",
     *     in="path",
     *     description="ID of the user detail",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="User detail data",
     *     @OA\JsonContent(ref="#/components/schemas/UserDetail")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User detail not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="User Detail Not Found")
     *     )
     *   )
     * )
     */
    public function showUserDetails(UserDetail $userDetail)
    {
        return $this->returnData("UserDetail", new UserDetailResource($userDetail), "User Data");
    }



    /**
     * @OA\Post(
     *     path="/api/users/import-users-from-excel",
     *     tags={"User"},
     *     summary="Import users from an Excel file",
     *     security={{"bearerAuth": {}}},
     *     description="This endpoint allows you to import multiple users from an Excel file (.xlsx) and validate the data.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="file",
     *                     description="Excel file (.xlsx) containing user data"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users imported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Users imported successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file format or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid file format. Please upload an Excel file (.xlsx).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Data validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="End time must be later than start time for user 'John Doe'.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error during processing",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while processing the file: [error details]")
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
    public function importUsersFromExcel(Request $request)
    {
        // Validator for the file upload
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            return $this->returnError('Invalid file format. Please upload an Excel file (.xlsx).', 400);
        }

        $file = $request->file('file');

        try {
            // Read the file contents
            $users = Excel::toArray([], $file);

            if (empty($users) || empty($users[0])) {
                return $this->returnError('No data found in the Excel file.', 400);
            }

            $sheetData = $users[0];
            $headers = array_map('trim', $sheetData[0]);
            $headers = array_map('strtolower', $headers);

            // Required fields that must be in the Excel file
            $requiredFields = [
                'name',
                'email',
                'password',
                'phone',
                'contact_phone',
                'national_id',
                'department_id',
                'gender',
                'salary',
                'working_hours_day',
                'overtime_hours',
                'start_time',
                'end_time',
                'emp_type',
                'hiring_date',
                'role',
                'location_id',
                'work_type_id',
            ];

            $errors = [];
            $missingHeaders = [];

            // Check for missing headers in the Excel file
            foreach ($requiredFields as $requiredField) {
                if (!in_array($requiredField, $headers)) {
                    $missingHeaders[] = $requiredField;
                }
            }

            if (!empty($missingHeaders)) {
                return $this->returnError("Invalid data format: Missing headers in the Excel file. Missing: " . implode(', ', $missingHeaders), 400);
            }

            // Regex patterns for validation
            $regexPhone = '/^(010|011|012|015)\d{8}$/';
            $regexEmail = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
            $regexNationalID = '/^\d{14}$/';

            $allErrors = [];

            DB::beginTransaction();

            for ($i = 1; $i < count($sheetData); $i++) {
                $row = array_combine($headers, $sheetData[$i]);
                $row = array_map('trim', $row);

                $rowErrors = [];

                // Validate required fields
                foreach ($requiredFields as $field) {
                    if (empty($row[$field])) {
                        $rowErrors[] = "Missing or empty required field '$field'";
                    }
                }

                // Validate email format
                if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Invalid email format in row " . ($i + 1);
                }

                // Validate phone format
                if (!preg_match($regexPhone, $row['phone'])) {
                    $rowErrors[] = "Invalid phone format in row " . ($i + 1) . ". Phone should match format 010, 011, 012, 015 followed by 8 digits.";
                }

                // Validate contact phone format
                if (!preg_match($regexPhone, $row['contact_phone'])) {
                    $rowErrors[] = "Invalid contact phone format in row " . ($i + 1) . ". Phone should match format 010, 011, 012, 015 followed by 8 digits.";
                }

                // Validate national ID format
                if (!preg_match($regexNationalID, $row['national_id'])) {
                    $rowErrors[] = "Invalid national ID format in row " . ($i + 1) . ". National ID should be 14 digits.";
                }

                // Validate gender
                if (!in_array(strtolower($row['gender']), ['male', 'female', 'm', 'f'])) {
                    $rowErrors[] = "Invalid gender value in row " . ($i + 1) . ". Allowed values are 'male', 'female', 'm', 'f'.";
                }

                // Validate location_id and work_type_id
                $locationIds = isset($row['location_id']) ? json_decode($row['location_id'], true) : [];
                $workTypeIds = isset($row['work_type_id']) ? json_decode($row['work_type_id'], true) : [];

                if (!is_array($locationIds)) {
                    $rowErrors[] = "The 'location_id' field must be an array of numeric values in row " . ($i + 1);
                } else {
                    $invalidLocationIds = array_diff($locationIds, Location::pluck('id')->toArray());
                    if (!empty($invalidLocationIds)) {
                        $rowErrors[] = "Invalid location_id values " . implode(', ', $invalidLocationIds) . " in row " . ($i + 1) . ".";
                    }
                }

                if (!is_array($workTypeIds)) {
                    $rowErrors[] = "The 'work_type_id' field must be an array of numeric values in row " . ($i + 1);
                } else {
                    $invalidWorkTypeIds = array_diff($workTypeIds, WorkType::pluck('id')->toArray());
                    if (!empty($invalidWorkTypeIds)) {
                        $rowErrors[] = "Invalid work_type_id values " . implode(', ', $invalidWorkTypeIds) . " in row " . ($i + 1) . ".";
                    }
                }

                // Check if department exists
                $department = Department::find((int) $row['department_id']);
                if (!$department) {
                    $rowErrors[] = "Department not found for ID {$row['department_id']} in row " . ($i + 1);
                }

                // Time validation
                if (isset($row['start_time'], $row['end_time']) && $row['end_time'] <= $row['start_time']) {
                    $rowErrors[] = "End time must be later than start time for user '{$row['name']}' in row " . ($i + 1);
                }

                if (!empty($rowErrors)) {
                    $allErrors[] = "Row " . ($i + 1) . ": " . implode(', ', $rowErrors);
                    continue;
                }

                try {
                    // Create user
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => strtolower($row['email']),
                        'password' => bcrypt($row['password']),
                        'phone' => $row['phone'],
                        'contact_phone' => $row['contact_phone'],
                        'national_id' => $row['national_id'],
                        'code' => strtoupper(substr(Str::slug($department->name), 0, 4)) . '-' . mt_rand(1000, 9999),
                        'department_id' => (int) $row['department_id'],
                        'gender' => $row['gender'],
                    ]);

                    $salary = $row['salary'];
                    $workingHoursDay = $row['working_hours_day'];
                    $overtimeHours = $row['overtime_hours'];
                    $hourlyRate = ($salary / 22) / $workingHoursDay;
                    $overtimeHourlyRate = (($salary / 30) / $workingHoursDay) * $overtimeHours;

                    // Create user details
                    UserDetail::create([
                        'salary' => $salary,
                        'working_hours_day' => $workingHoursDay,
                        'hourly_rate' => $hourlyRate,
                        'overtime_hourly_rate' => $overtimeHourlyRate,
                        'overtime_hours' => $overtimeHours,
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'emp_type' => $row['emp_type'],
                        'hiring_date' => $row['hiring_date'],
                        'user_id' => $user->id,
                    ]);

                    // Attach locations and work types
                    $user->user_locations()->attach($locationIds);
                    $user->work_types()->attach($workTypeIds);
                } catch (\Illuminate\Database\QueryException $e) {
                    $errorMessage = $this->cleanDuplicateEntryError($e->getMessage());
                    $allErrors[] = "Row " . ($i + 1) . ": " . $errorMessage;
                } catch (\Exception $e) {
                    $allErrors[] = "Row " . ($i + 1) . ": " . $e->getMessage();
                }
            }

            if (!empty($allErrors)) {
                DB::rollBack();
                return $this->returnError(implode("\n", $allErrors), 400);
            }

            DB::commit();

            return $this->returnSuccess('Users imported successfully.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('An error occurred while processing the file: ' . $e->getMessage(), 500);
        }
    }
}

