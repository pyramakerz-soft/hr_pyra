<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserDetailResource;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\LoginResource;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDetail;
use App\Traits\HelperTrait;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ResponseTrait, HelperTrait;
    public function __construct()
    {
        $this->middleware('auth:api')->except(['store', 'login']);
    }

    public function index()
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
        }
        if (request()->has('search')) {
            $users = UserResource::collection(
                User::where('name', 'like', '%' . request()->get('search', '') . '%')
                    ->orWhere('code', 'like', '%' . request()->get('search', '') . '%')->get()
            );
            if ($users->isEmpty()) {
                return $this->returnError('No Users Found');
            }
            $data[] = [
                'users' => UserResource::collection($users),

            ];
        } else {
            $users = User::paginate(5);
            $data[] = [
                'users' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'next_page_url' => $users->nextPageUrl(),
                    'previous_page_url' => $users->previousPageUrl(),
                    'last_page' => $users->lastPage(),
                    'total' => $users->total(),
                ],

            ];
        }

        return $this->returnData("data", $data, "Users Data");
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
        return $this->createOrUpdateUser($request);
    }
    public function importUsersFromExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx'
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

            foreach ($requiredFields as $requiredField) {
                if (!in_array($requiredField, $headers)) {
                    return $this->returnError("Invalid data format: Missing '$requiredField' header in the Excel file. Headers found: " . implode(', ', $headers), Response::HTTP_BAD_REQUEST);
                }
            }

            $results = [];

            for ($i = 1; $i < count($sheetData); $i++) {
                $row = array_combine($headers, $sheetData[$i]);

                $row = array_map('trim', $row);

                foreach ($requiredFields as $field) {
                    if (!isset($row[$field]) || empty($row[$field])) {
                        return $this->returnError("Missing or empty required field '$field' in row " . ($i + 1), Response::HTTP_BAD_REQUEST);
                    }
                }

                $department = Department::find((int) $row['department_id']);
                if (!$department) {
                    return $this->returnError('Invalid department selected in row ' . ($i + 1), Response::HTTP_BAD_REQUEST);
                }

                do {
                    $departmentPrefix = substr(Str::slug($department->name), 0, 4);
                    $randomDigits = mt_rand(1000, 9999);
                    $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
                } while (User::where('code', $code)->exists());

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

                $start_time = $row['start_time'];
                $end_time = $row['end_time'];

                if ($end_time <= $start_time) {
                    return $this->returnError('End time must be later than start time for user ' . $row['name'], Response::HTTP_BAD_REQUEST);
                }

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
            }

            return $this->returnData('results', $results, 'Users Imported from Excel successfully.');

        } catch (\Exception $e) {
            return $this->returnError('Failed to import users from Excel: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }







    private function createOrUpdateUser(RegisterRequest $request)
    {
        $finalData = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to create users', 403);
        }

        $department = Department::find((int) $request->department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4);
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        $imageUrl = $this->uploadImage($request);
        if (!$imageUrl) {
            return $this->returnError('Image upload failed', Response::HTTP_BAD_REQUEST);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'contact_phone' => $request->contact_phone,
            'national_id' => $request->national_id,
            'code' => $code,
            'gender' => $request->gender,
            'department_id' => (int) $request->department_id,
            'image' => $imageUrl,
            'serial_number' => null,
        ]);

        $finalData['user'] = $user;

        $salary = $request->salary;
        $working_hours_day = $request->working_hours_day;
        $overtime_hours = $request->overtime_hours;
        $hourly_rate = ($salary / 22) / $working_hours_day;
        $overtime_hourly_rate = (($salary / 30) / $working_hours_day) * $overtime_hours;
        $start_time = $request->start_time;
        $end_time = $request->end_time;

        if ($end_time <= $start_time) {
            return $this->returnError('End time must be later than start time', Response::HTTP_BAD_REQUEST);
        }

        $userDetail = UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hourly_rate' => $overtime_hourly_rate,
            'overtime_hours' => $overtime_hours,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'emp_type' => $request->emp_type,
            'hiring_date' => $request->hiring_date,
            'user_id' => $user->id,
        ]);

        $finalData['user_detail'] = $userDetail;

        if (!$user || !$userDetail) {
            return $this->returnError('Failed to Store User');
        }

        $user->syncRoles($request->input('roles', []));

        $newLocations = $request->input('location_id', []);
        foreach ($newLocations as $locationId) {
            $LocationAssignedToUser = $user->user_locations()->wherePivot('location_id', $locationId)->exists();
            if (!$LocationAssignedToUser) {
                $user->user_locations()->attach($locationId);
            }
        }

        $newWorkTypes = $request->input('work_type_id', []);
        foreach ($newWorkTypes as $workTypeId) {
            $workTypeAssignedToUser = $user->work_types()->wherePivot('work_type_id', $workTypeId)->exists();
            if (!$workTypeAssignedToUser) {
                $user->work_types()->attach($workTypeId);
            }
        }

        return $this->returnData("data", $finalData, "User Created");
    }


    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $errors = [];

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            $errors['email'] = 'Wrong Email';
        } else {
            if (!Hash::check($credentials['password'], $user->password)) {
                $errors['password'] = 'Wrong password';
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Validation errors occurred.',
                'errors' => $errors,
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();
        // Check if serial number is present in the request
        $serialNumber = $request->serial_number;

        if ($user && $serialNumber) {
            if (is_null($user->serial_number)) {
                $user->serial_number = $serialNumber;
                $user->save();
            } else {
                if ($user->serial_number !== $serialNumber) {
                    return $this->returnError('Serial number does not match', 406);
                }
            }
        }

        return response()->json([
            "result" => "true",
            'token' => $token,
        ], Response::HTTP_OK);
    }

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
        $finalData = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to update users', 403);
        }
        $department_id = $request->has('department_id') ? $request->department_id : $user->department_id;
        // dd($department_id);
        $department = Department::find($department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4);
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        $imageUrl = $this->uploadImage($request);

        if (!$imageUrl) {
            $imageUrl = $user->image;
        }

        // Update user information
        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'phone' => $request->phone ?? $user->phone,
            'contact_phone' => $request->contact_phone ?? $user->contact_phone,
            'national_id' => $request->national_id ?? $user->national_id,
            'code' => $code,
            'gender' => $request->gender ?? $user->gender,
            'department_id' => (int) $department_id,
            'image' => $imageUrl,
        ]);

        $finalData['user'] = $user;

        // Update user detail information
        $userDetail = UserDetail::where('user_id', $user->id)->first();
        if (!$userDetail) {
            return $this->returnError('No User Detail Found for this User');
        }
        $salary = $request->salary; // Example: 24000
        $working_hours_day = $request->working_hours_day; // Example: 8
        if ($working_hours_day === null || $working_hours_day == 0) {
            $hourly_rate = 0;
        } else {
            $hourly_rate = ($salary / 30) / $working_hours_day;
        }
        $start_time = $request->start_time ? Carbon::parse($request->start_time)->format("H:i:s") : $userDetail->start_time;
        $end_time = $request->end_time ? Carbon::parse($request->end_time)->format("H:i:s") : $userDetail->end_time;
        if ($end_time <= $start_time) {
            return $this->returnError('End time must be later than start time', Response::HTTP_BAD_REQUEST);
        }

        $userDetail->update([
            'salary' => $salary ?? $userDetail->salary,
            'working_hours_day' => $working_hours_day ?? $userDetail->working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => $request->overtime_hours ?? $userDetail->overtime_hours,
            'start_time' => $start_time ?? $userDetail->start_time,
            'end_time' => $end_time ?? $userDetail->end_time,
            'emp_type' => $request->emp_type ?? $userDetail->emp_type,
            'hiring_date' => $request->hiring_date ?? $userDetail->hiring_date,
            'user_id' => $user->id,
        ]);

        $finalData['user_detail'] = $userDetail;

        if (!$user || !$userDetail) {
            return $this->returnError('Failed to Update User');
        }

        // Update Assigning Roles to User
        $newRoles = $request->input('roles') ?? $user->getRoleNames()->toArray();
        $user->syncRoles($newRoles);

        // Update Assigning Locations to User
        $newLocations = $request->input('location_id') ?? $user->user_locations()->pluck('locations.id')->toArray();
        $user->user_locations()->sync($newLocations);

        // Update Assigning WorkTypes to User
        $newWorkTypes = $request->input('work_type_id') ?? $user->work_types()->pluck('work_types.id')->toArray();
        $user->work_types()->sync($newWorkTypes);

        return $this->returnData("data", $finalData, "User Updated");
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

    public function logout()
    {
        $user = auth()->user();
        Auth::logout();
        return $this->returnData("user", $user, "You are logged out");
    }
    public function profile()
    {
        $authUser = Auth::user();
        $user = User::where('id', $authUser->id)->first();
        if (!$user) {
            return $this->returnError('No User Found');
        }

        return $this->returnData("User", new LoginResource($user), "User Data");
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
