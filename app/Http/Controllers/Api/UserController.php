<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Http\Resources\LoginResource;
// use App\Models\Request;
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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('user_detail', 'user_vacations', 'department.user_holidays', 'roles.permissions')->get();
        if ($users->isEmpty()) {
            return $this->returnError('No Users Found');
        }
        $data['users'] = UserResource::collection($users);
        // $data['users'] = $users;

        return $this->returnData("data", $data, "Users Data");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegisterRequest $request)
    {
        if (!Auth::user()->hasRole('Hr')) {
            return $this->returnError('Unauthorized', 403);
        }
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'contact_phone' => $request->contact_phone,
            'gender' => $request->gender,
            'department_id' => (int) $request->department_id,

        ]);
        if (!$user) {
            return $this->returnError('Failed to Store User');

        }
        $user->syncRoles($request->input('roles', []));
        return $this->returnData("user", $user, "User Created");
    }
    // public function login(LoginRequest $request)
    // {
    //     $credentials = $request->only('email', 'password');
    //     $token = JWTAuth::attempt($credentials);
    //     $authUser = Auth::user()->load('user_detail');
    //     // dd($authUser);
    //     $userDetail = UserDetail::findOrFail($authUser->id)->toArray();

    //     $authUserArray = $authUser->toArray();
    //     $roleName = $authUser->getRoleName();
    //     if (!$roleName) {
    //         $authUserArray['role_name'] = null;
    //     }
    //     $authUserArray['role_name'] = $roleName;

    //     $user = array_merge($authUserArray, $userDetail);
    //     if (!$token) {
    //         return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
    //     }
    //     return response()->json([
    //         "result" => "true",
    //         'user' => new LoginResource($user),
    //         'token' => $token,
    //     ], Response::HTTP_OK);
    // }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        $authUser = Auth::user()->load('user_detail');
        return response()->json([
            "result" => "true",
            'user' => new LoginResource($authUser),
            'token' => $token,
        ], Response::HTTP_OK);
    }
    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user = $user->with('user_detail', 'user_vacations', 'department.user_holidays', 'roles.permissions')->where('id', $user->id)->get();
        return $this->returnData("User", UserResource::collection($user), "User Data");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        if (!$user) {
            return $this->returnError('User Not Found');
        }
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
