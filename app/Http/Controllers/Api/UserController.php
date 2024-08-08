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
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return $this->returnError('No Users Found');
        }
        return $this->returnData("users", $users, "Users Data");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RegisterRequest $request)
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

    /**
     * Display the specified resource.
     */
    public function show()
    {
        $authUser = Auth::user();
        return response()->json(["result" => 'true', 'message' => 'User Profile', 'user' => $authUser], Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        dd($user->toArray());
        $user->update($request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->returnData("user", $user, "user deleted");

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
    public function logout()
    {
        $user = auth()->user();
        Auth::logout();
        return $this->returnData("user", $user, "You are logged out");
    }
}
