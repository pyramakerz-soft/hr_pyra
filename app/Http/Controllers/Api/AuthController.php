<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        $this->middleware('auth:api')->except(['createUser', 'login']);
    }
    public function getAllUsers()
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return $this->returnError('No Users Found');
        }
        return $this->returnData("users", $users, "Users Data");
    }
    public function createUser(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'contact_phone' => $request->contact_phone,
            'gender' => $request->gender,
            'department_id' => (int) $request->department_id,

        ]);

        return $this->returnData("users", $user, "User Created");
    }
    public function updateUser(UpdateUserRequest $request, User $user)
    {
        dd($user->toArray());
        $user->update($request->validated());
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
            'user' => Auth::user(),
            'token' => $token,
        ], Response::HTTP_OK);
    }
    public function profile()
    {
        $authUser = Auth::user();

        return response()->json(["result" => 'true', 'message' => 'User Profile', 'user' => $authUser], Response::HTTP_OK);
    }
    public function logout()
    {
        $user = auth()->user();
        Auth::logout();
        return $this->returnData("user", $user, "You are logged out");
    }
    public function deleteUser(User $user)
    {
        if (!$user) {
            return $this->returnError("Not Found");
        }
        $user->delete();
        return $this->returnData("user", $user, "user deleted");

    }
}
