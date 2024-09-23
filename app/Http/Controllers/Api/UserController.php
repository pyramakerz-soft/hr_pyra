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
use App\Services\Api\AuthorizationService;
use App\Services\Api\User\UserDetailService;
use App\Services\Api\User\UserService;
use App\Traits\ResponseTrait;
use App\Traits\UserTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    use ResponseTrait, UserTrait;
    protected $userService;
    protected $userDetailService;
    protected $authorizationService;

    public function __construct(UserService $userService, UserDetailService $userDetailService, AuthorizationService $authorizationService)
    {
        $this->userService = $userService;
        $this->userDetailService = $userDetailService;
        $this->authorizationService = $authorizationService;
        $this->middleware('auth:api')->except(['store']);
    }

    public function index()
    {
        $authUser = Auth::user();

        $this->authorizationService->authorizeHrUser($authUser);

        $search = request()->get('search', null);
        $usersData = $this->userService->getAllUsers($search);

        if (!$usersData) {
            return $this->returnError('No Users Found');
        }
        return $this->returnData("data", $usersData, "Users Data");
    }
    public function ManagerNames()
    {
        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);

        $data = $this->userService->getManagerNames();
        return $this->returnData('managerNames', $data, 'manager names');

    }

    public function store(RegisterRequest $request)
    {

        $data = [];

        $authUser = Auth::user();
        $this->authorizationService->authorizeHrUser($authUser);
        $user = $this->userService->createUser($request->validated());

        if (!$user) {
            return $this->returnError('Failed to Store User');
        }

        $userDetail = $this->userDetailService->createUserDetail($user, $request->validated());
        if (!$userDetail) {
            return $this->returnError('Failed to Store User Detail');
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

    public function importUsersFromExcel(Request $request)
    {
        // Validator for the file upload
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            return $this->returnError('Invalid file format. Please upload an Excel file (.xlsx).', Response::HTTP_BAD_REQUEST);
        }

        $file = $request->file('file');

        try {
            // Read the file contents
            $users = Excel::toArray([], $file);

            if (empty($users) || empty($users[0])) {
                return $this->returnError('No data found in the Excel file.', Response::HTTP_BAD_REQUEST);
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
                return $this->returnError("Invalid data format: Missing headers in the Excel file. Missing: " . implode(', ', $missingHeaders), Response::HTTP_BAD_REQUEST);
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
                return $this->returnError(implode("\n", $allErrors), Response::HTTP_BAD_REQUEST);
            }

            DB::commit();

            return $this->returnSuccess('Users imported successfully.', Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('An error occurred while processing the file: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Helper methods for success and error responses
    private function returnSuccess($message, $statusCode)
    {
        return response()->json([
            'result' => 'true',
            'status' => $statusCode,
            'message' => $message,
            'data' => [],
        ], $statusCode);
    }

    // private function returnError($message, $statusCode)
    // {
    //     return response()->json([
    //         'result' => 'false',
    //         'status' => $statusCode,
    //         'message' => $message,
    //         'data' => [],
    //     ], $statusCode);
    // }

    /**
     * Cleans the duplicate entry error message to remove technical details.
     */
    private function cleanDuplicateEntryError($message)
    {
        if (strpos($message, 'Duplicate entry') !== false) {
            $matches = [];
            preg_match("/Duplicate entry '(.*?)' for key '(.*?)'/", $message, $matches);
            return isset($matches[1], $matches[2]) ? "Duplicate entry '{$matches[1]}' for field '{$matches[2]}'" : $message;
        }
        return $message;
    }

    // public function importUsersFromExcel(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'file' => 'required|mimes:xlsx',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->returnError('Invalid file format. Please upload an Excel file (.xlsx).', Response::HTTP_BAD_REQUEST);
    //     }

    //     $file = $request->file('file');

    //     try {
    //         $users = Excel::toArray([], $file);

    //         if (empty($users) || empty($users[0])) {
    //             return $this->returnError('No data found in the Excel file.', Response::HTTP_BAD_REQUEST);
    //         }

    //         $sheetData = $users[0];

    //         $headers = array_map('trim', $sheetData[0]);

    //         $headers = array_map('strtolower', $headers);

    //         $requiredFields = ['name', 'email', 'password', 'phone', 'contact_phone', 'national_id', 'department_id', 'gender', 'salary', 'working_hours_day', 'overtime_hours', 'start_time', 'end_time', 'emp_type', 'hiring_date', 'roles', 'location_id', 'work_type_id'];

    //         foreach ($requiredFields as $requiredField) {
    //             if (!in_array($requiredField, $headers)) {
    //                 return $this->returnError("Invalid data format: Missing '$requiredField' header in the Excel file. Headers found: " . implode(', ', $headers), Response::HTTP_BAD_REQUEST);
    //             }
    //         }

    //         $results = [];

    //         for ($i = 1; $i < count($sheetData); $i++) {
    //             $row = array_combine($headers, $sheetData[$i]);

    //             $row = array_map('trim', $row);

    //             foreach ($requiredFields as $field) {
    //                 if (!isset($row[$field]) || empty($row[$field])) {
    //                     return $this->returnError("Missing or empty required field '$field' in row " . ($i + 1), Response::HTTP_BAD_REQUEST);
    //                 }
    //             }

    //             $department = Department::find((int) $row['department_id']);
    //             if (!$department) {
    //                 return $this->returnError('Invalid department selected in row ' . ($i + 1), Response::HTTP_BAD_REQUEST);
    //             }

    //             do {
    //                 $departmentPrefix = substr(Str::slug($department->name), 0, 4);
    //                 $randomDigits = mt_rand(1000, 9999);
    //                 $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
    //             } while (User::where('code', $code)->exists());

    //             $user = User::create([
    //                 'name' => $row['name'],
    //                 'email' => $row['email'],
    //                 'password' => bcrypt($row['password']),
    //                 'phone' => $row['phone'],
    //                 'contact_phone' => $row['contact_phone'],
    //                 'national_id' => $row['national_id'],
    //                 'code' => $code,
    //                 'department_id' => (int) $row['department_id'],
    //                 'gender' => $row['gender'],
    //                 'serial_number' => null,
    //             ]);

    //             $salary = $row['salary'];
    //             $working_hours_day = $row['working_hours_day'];
    //             $overtime_hours = $row['overtime_hours'];
    //             $hourly_rate = ($salary / 22) / $working_hours_day;
    //             $overtime_hourly_rate = (($salary / 30) / $working_hours_day) * $overtime_hours;

    //             $start_time = $row['start_time'];
    //             $end_time = $row['end_time'];

    //             if ($end_time <= $start_time) {
    //                 return $this->returnError('End time must be later than start time for user ' . $row['name'], Response::HTTP_BAD_REQUEST);
    //             }

    //             $userDetail = UserDetail::create([
    //                 'salary' => $salary,
    //                 'working_hours_day' => $working_hours_day,
    //                 'hourly_rate' => $hourly_rate,
    //                 'overtime_hourly_rate' => $overtime_hourly_rate,
    //                 'overtime_hours' => $overtime_hours,
    //                 'start_time' => $start_time,
    //                 'end_time' => $end_time,
    //                 'emp_type' => $row['emp_type'],
    //                 'hiring_date' => $row['hiring_date'],
    //                 'user_id' => $user->id,
    //             ]);

    //             $results[] = [
    //                 'user' => $user,
    //                 'user_detail' => $userDetail,
    //             ];
    //         }

    //         return $this->returnData('results', $results, 'Users Imported from Excel successfully.');

    //     } catch (\Exception $e) {
    //         return $this->returnError('Failed to import users from Excel: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
    //     }
    // }

    public function show(User $user)
    {
        $authUser = Auth::user();

        $this->authorizationService->authorizeHrUser($authUser);
        $userDetail = UserDetail::where('user_id', $user->id)->first();
        return $this->returnData("User", new UserDetailResource($userDetail), "User Data");
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [];
        $authUser = Auth::user();

        $this->authorizationService->authorizeHrUser($authUser);
        $updatedUser = $this->userService->updateUser($user, $request->validated());
        if (!$updatedUser) {
            return $this->returnError('Failed to Update User');
        }

        $userDetail = UserDetail::where('user_id', $user->id)->first();
        if (!$userDetail) {
            return $this->returnError('No User Detail Found for this User');
        }

        $updatedUserDetail = $this->userDetailService->updateUserDetail($userDetail, $request->validated());
        if (!$updatedUserDetail) {
            return $this->returnError('Failed to Update User Detail');
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

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        $this->authorizationService->authorizeHrUser($authUser);
        $user->delete();
        return $this->returnData("user", $user, "user deleted");
    }

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

}
