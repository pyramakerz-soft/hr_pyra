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
        $users = User::paginate(5);
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
            'national_id' => $request->national_id,
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

    public function show(User $user)
    {
        return $this->returnData("User", $user, "User Data");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        if (!$user) {
            return $this->returnError('User Not Found');
        }
        $user->update($request->validated());

        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        $user->assignRole($request->input('roles'));
        return $this->returnData("user", $user, "User Updated");

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
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