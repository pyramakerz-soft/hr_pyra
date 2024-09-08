<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserDetailResource;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\Api\UserDetailService;
use App\Services\Api\UserService;
use App\Traits\HelperTrait;
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
    use ResponseTrait, HelperTrait, UserTrait;
    protected $userService;
    protected $userDetailService;

    public function __construct(UserService $userService, UserDetailService $userDetailService)
    {
        $this->userService = $userService;
        $this->userDetailService = $userDetailService;

        $this->middleware('auth:api')->except(['store']);
    }

    public function index()
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to update users', 403);
        }

        $search = request()->get('search', null);
        $usersData = $this->userService->getAllUsers($search);

        if (!$usersData) {
            return $this->returnError('No Users Found');
        }

        return $this->returnData("data", $usersData, "Users Data");
    }
    public function ManagerNames()
    {
        $managerData = DB::table('model_has_roles')
            ->where('role_id', 2)
            ->get();

        foreach ($managerData as $manager) {
            $user = User::find($manager->model_id);
            if ($user) {
                $data[] = [
                    'manager_id' => $user->id,
                    'manager_name' => $user->name,
                ];
            }
        }

        return $this->returnData('managerNames', $data, 'manager names');

    }

    public function store(RegisterRequest $request)
    {
        $data = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to create users', 403);
        }
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
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            return $this->returnError('Invalid file format. Please upload an Excel file (.xlsx).', Response::HTTP_BAD_REQUEST);
        }

        $file = $request->file('file');

        try {
            $users = Excel::toArray([], $file);

            if (empty($users) || empty($users[0])) {
                return $this->returnError('No data found in the Excel file.', Response::HTTP_BAD_REQUEST);
            }

            $sheetData = $users[0];
            $headers = array_map('trim', $sheetData[0]);
            $headers = array_map('strtolower', $headers);

            $requiredFields = ['name', 'email', 'password', 'phone', 'contact_phone', 'national_id', 'department_id', 'gender', 'salary', 'working_hours_day', 'overtime_hours', 'start_time', 'end_time', 'emp_type', 'hiring_date', 'roles', 'location_id', 'work_type_id'];

            $errors = [];
            $missingHeaders = [];

            foreach ($requiredFields as $requiredField) {
                if (!in_array($requiredField, $headers)) {
                    $missingHeaders[] = $requiredField;
                }
            }

            if (!empty($missingHeaders)) {
                $errors[] = "Invalid data format: Missing headers in the Excel file. Missing: " . implode(', ', $missingHeaders);
            }

            if (!empty($errors)) {
                return $this->returnError(implode("\n", $errors), Response::HTTP_BAD_REQUEST);
            }

            $results = [];

            for ($i = 1; $i < count($sheetData); $i++) {
                $row = array_combine($headers, $sheetData[$i]);
                $row = array_map('trim', $row);

                $rowErrors = [];

                foreach ($requiredFields as $field) {
                    if (!isset($row[$field]) || empty($row[$field])) {
                        $rowErrors[] = "Missing or empty required field '$field'";
                    }
                }

                if (!empty($rowErrors)) {
                    $errors[] = "Row " . ($i + 1) . ": " . implode(", ", $rowErrors);
                    continue;
                }

                $department = Department::find((int) $row['department_id']);
                if (!$department) {
                    $errors[] = 'Invalid department in row ' . ($i + 1);
                    continue;
                }

                do {
                    $departmentPrefix = substr(Str::slug($department->name), 0, 4);
                    $randomDigits = mt_rand(1000, 9999);
                    $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
                } while (User::where('code', $code)->exists());

                $start_time = $row['start_time'];
                $end_time = $row['end_time'];

                if ($end_time <= $start_time) {
                    $errors[] = 'End time must be later than start time for user ' . $row['name'] . ' in row ' . ($i + 1);
                    continue;
                }

                try {
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'password' => bcrypt($row['password']),
                        'phone' => $row['phone'],
                        'contact_phone' => $row['contact_phone'],
                        'national_id' => $row['national_id'],
                        'code' => $code,
                        'department_id' => (int) $row['department_id'],
                        'gender' => $row['gender'],
                        'serial_number' => null,
                    ]);

                    $salary = $row['salary'];
                    $working_hours_day = $row['working_hours_day'];
                    $overtime_hours = $row['overtime_hours'];
                    $hourly_rate = ($salary / 22) / $working_hours_day;
                    $overtime_hourly_rate = (($salary / 30) / $working_hours_day) * $overtime_hours;

                    $userDetail = UserDetail::create([
                        'salary' => $salary,
                        'working_hours_day' => $working_hours_day,
                        'hourly_rate' => $hourly_rate,
                        'overtime_hourly_rate' => $overtime_hourly_rate,
                        'overtime_hours' => $overtime_hours,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'emp_type' => $row['emp_type'],
                        'hiring_date' => $row['hiring_date'],
                        'user_id' => $user->id,
                    ]);

                    $results[] = [
                        'user' => $user,
                        'user_detail' => $userDetail,
                    ];
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->getCode() == '23000') { // SQLSTATE[23000]: Integrity constraint violation
                        // This handles duplicate entry errors
                        $errorMessage = 'Duplicate entry found for email "' . $row['email'] . '" in row ' . ($i + 1);
                        $errors[] = $errorMessage;
                    } else {
                        $errors[] = 'Failed to import user in row ' . ($i + 1) . ': ' . $e->getMessage();
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Failed to import user in row ' . ($i + 1) . ': ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                return $this->returnError(implode("\n", $errors), Response::HTTP_BAD_REQUEST);
            }

            return $this->returnData('results', $results, 'Users Imported from Excel successfully.');

        } catch (\Exception $e) {
            return $this->returnError('Failed to import users from Excel: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
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

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
        }
        $userDetail = UserDetail::where('user_id', $user->id)->first();
        return $this->returnData("User", new UserDetailResource($userDetail), "User Data");
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to update users', 403);
        }

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

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to delete users', 403);
        }

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
