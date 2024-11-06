<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserDetailResource;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\WorkType;
use App\Services\Api\User\UserDetailService;
use App\Services\Api\User\UserService;
use App\Traits\ResponseTrait;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
class UserController extends Controller
{
    use ResponseTrait, UserTrait;
    protected $userService;
    protected $userDetailService;

    public function __construct(UserService $userService, UserDetailService $userDetailService)
    {

        // Ay haga sfaasdasdasd
        $this->middleware('auth:api')->except(['store']);

        $this->userService = $userService;
        $this->userDetailService = $userDetailService;

        // Applying middleware for specific actions based on permissions
        // $this->middleware('permission:user-list')->only(['index', 'getAllUsersNames', 'show']);
        // $this->middleware('permission:user-create')->only(['store', 'importUsersFromExcel']);
        // $this->middleware('permission:user-edit')->only(['update', 'updatePassword']);
        // $this->middleware('permission:user-delete')->only(['destroy']);
    }
    /**
     * @OA\Get(
     *     path="/api/auth/getAllUsers",
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
     *     @OA\Response(response=200, description="List of users"),
     *     @OA\Response(response=404, description="No users found")
     * )
     */
    public function index()
    {

        $search = request()->get('search', null);
        $usersData = $this->userService->getAllUsers($search);

        if (!$usersData) {
            return $this->returnError('No Users Found');
        }
        return $this->returnData("data", $usersData, "Users Data");
    }
    /**
     * @OA\Get(
     *     path="/api/manager_names",
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
        $data = $this->userService->getManagerNames();
        return $this->returnData('managerNames', $data, 'manager names');

    }
    /**
     * @OA\Post(
     *     path="/api/auth/create_user",
     *     tags={"User"},
     *     summary="Create a new user",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "phone", "department_id"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@test.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="phone", type="string", example="01203376559"),
     *             @OA\Property(property="contact_phone", type="string", example="01203376669"),
     *             @OA\Property(property="national_id", type="string", example="30201010214335"),
     *             @OA\Property(property="gender", type="string", example="m"),
     *             @OA\Property(property="department_id", type="integer", example=1),
     *             @OA\Property(property="image", type="string", format="binary", description="Profile image"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"), description="Roles to assign",example="employee"),
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
    public function store(RegisterRequest $request)
    {

        $data = [];

        $user = $this->userService->createUser($request->validated());

        if (!$user) {
            return $this->returnError('Failed to Store User');
        }

        $userDetail = $this->userDetailService->createUserDetail($user, $request->validated());
        if ($userDetail instanceof JsonResponse) {
            return $userDetail;
        }
        $this->assignRoles($user, $request['roles'] ?? []);
        $this->assignLocations($user, $request['location_id'] ?? []);
        $this->assignWorkTypes($user, $request['work_type_id'] ?? []);

        $data = [
            'user' => $user,
            'user_detail' => $userDetail,
        ];

        return $this->returnData("data", $data, "User Created");
    }
    /**
     * @OA\Post(
     *     path="/api/auth/import-users-from-excel",
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
                'roles',
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

    /**
     * @OA\Get(
     *     path="/api/auth/get_user_by_id/{user}",
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
     *     path="/api/auth/update_user/{user}",
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
        $data = [];

        $updatedUser = $this->userService->updateUser($user, $request->validated());
        if (!$updatedUser) {
            return $this->returnError('Failed to Update User');
        }

        $userDetail = UserDetail::where('user_id', $user->id)->first();
        if (!$userDetail) {
            return $this->returnError('No User Detail Found for this User');
        }
        // Update user detail
        $updatedUserDetail = $this->userDetailService->updateUserDetail($userDetail, $request->validated());
        if ($updatedUserDetail instanceof JsonResponse) {
            return $updatedUserDetail; // Return the error response from the service
        }

        // Assign roles, locations, and work types
        if ($request->has('roles')) {
            $this->assignRoles($user, $request->input('roles'));
        }

        if ($request->has('location_id')) {
            $this->assignLocations($user, $request->input('location_id'));
        }

        if ($request->has('work_type_id')) {
            $this->assignWorkTypes($user, $request->input('work_type_id'));
        }

        $data = [
            'user' => $updatedUser,
            'user_detail' => $updatedUserDetail,
        ];

        return $this->returnData("data", $data, "User Updated");

    }
    /**
     * @OA\Delete(
     *     path="/api/auth/delete_user/{user}",
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
     *     path="/api/auth/users_by_name",
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
     *     path="/api/employees_per_month",
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

}
