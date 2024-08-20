<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\LoginResource;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDetail;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ResponseTrait;

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

        $users = User::paginate(5);
        if ($users->isEmpty()) {
            return $this->returnError('No Users Found');
        }
        $data['users'] = UserResource::collection($users);

        return $this->returnData("data", $data, "Users Data");
    }

    public function store(RegisterRequest $request)
    {
        $finalData = [];
        $authUser = Auth::user();

        // Check if the authenticated user has the 'Hr' role
        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to create users', 403);
        }

        // Get the department name based on the department_id
        $department = Department::find((int) $request->department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        // Generate a unique code
        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4); // Get the first 4 letters of the department name
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());

        // Create the user with the generated unique code
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'contact_phone' => $request->contact_phone,
            'national_id' => $request->national_id,
            'code' => $code, // Assign the generated code here
            'gender' => $request->gender,
            'department_id' => (int) $request->department_id,
        ]);

        $finalData['user'] = $user;

        // Calculate the hourly rate
        $salary = $request->salary; // Example: 24000
        $working_hours_day = $request->working_hours_day; // Example: 8
        $hourly_rate = ($salary / 30) / $working_hours_day;

        // Validate start and end time
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        if ($end_time <= $start_time) {
            return $this->returnError('End time must be later than start time', Response::HTTP_BAD_REQUEST);
        }

        // Create the user detail record
        $userDetail = UserDetail::create([
            'salary' => $salary,
            'working_hours_day' => $working_hours_day,
            'hourly_rate' => $hourly_rate,
            'overtime_hours' => $request->overtime_hours,
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

        // Assign roles to the user
        $user->syncRoles($request->input('roles', []));

        return $this->returnData("data", $finalData, "User Created");
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
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

        return $this->returnData("User", $user, "User Data");
    }

    public function update(UpdateUserRequest $request, User $user)
    {

        $finalData = [];
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
        }

        $department = Department::find($user->department_id);
        if (!$department) {
            return $this->returnError('Invalid department selected', Response::HTTP_BAD_REQUEST);
        }

        do {
            $departmentPrefix = substr(Str::slug($department->name), 0, 4);
            $randomDigits = mt_rand(1000, 9999);
            $code = strtoupper($departmentPrefix) . '-' . $randomDigits;
        } while (User::where('code', $code)->exists());
        $user->update([
            'name' => $request->name ?? $user->name,
            'email' => $request->email ?? $user->email,
            'password' => bcrypt($request->password) ?? $user->password,
            'phone' => $request->phone ?? $user->phone,
            'contact_phone' => $request->contact_phone ?? $user->contact_phone,
            'national_id' => $request->national_id ?? $user->national_id,
            'code' => $code,
            'gender' => $request->gender ?? $user->gender,
            'department_id' => (int) $department->id,
        ]);

        $finalData['user'] = $user;

        $userDetail = UserDetail::where('user_id', $user->id)->first();
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

        // Assign roles to the user
        DB::table('model_has_roles')->where('model_id', $user->id)->delete();

        $user->syncRoles($request->input('roles', []));

        return $this->returnData("data", $finalData, "User Updated");
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->hasRole('Hr')) {
            return $this->returnError('You are not authorized to Update users', 403);
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
    // public function AssignRole(Request $request, User $user)
    // {
    //     $this->validate($request, [
    //         'role' => ['required', 'string', 'exists:roles,name'],
    //     ]);
    //     $role = Role::findByName($request->role);
    //     $user->assignRole($role);
    //     return $this->returnData('user', $user, 'Role assigned to user successfully.');
    // }
}
