<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\LoginResource;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
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
        $users = User::with('user_detail', 'user_vacations', 'department.user_holidays', 'roles.permissions')->get();
        if ($users->isEmpty()) {
            return $this->returnError('No Users Found');
        }
        $data['users'] = UserResource::collection($users);

        return $this->returnData("data", $data, "Users Data");
    }

    public function store(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'contact_phone' => $request->contact_phone,
            'code' => $request->code,
            'gender' => $request->gender,
            'department_id' => (int) $request->department_id,

        ]);
        if (!$user) {
            return $this->returnError('Failed to Store User');

        }
        $user->syncRoles($request->input('roles', []));
        return $this->returnData("user", $user, "User Created");
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

    public function show()
    {
        $authUser = Auth::user();
        $user = $authUser::where('id', $authUser->id)->get();
        if (!$user) {
            return $this->returnError('No User Found');

        }
        return $this->returnData("User", LoginResource::collection($user), "User Data");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request)
    {
        $authUser = Auth::user();
        $authUser->update($request->validated());
        if (!$authUser) {
            return $this->returnError('User Not Found');
        }
        DB::table('model_has_roles')->where('model_id', $authUser->id)->delete();
        $authUser->assignRole($request->input('roles'));
        return $this->returnData("user", $authUser, "User Updated");

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $user = Auth::user();
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
        return response()->json(["result" => 'true', 'message' => 'User Profile', 'user' => $authUser], Response::HTTP_OK);
    }
    public function AssignRole(Request $request, User $user)
    {
        $this->validate($request, [
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);
        $role = Role::findByName($request->role);
        $user->assignRole($role);
        return $this->returnData('user', $user, 'Role assigned to user successfully.');
    }
}