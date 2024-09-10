<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        $this->middleware('auth:api')->except('login');
    }
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $email = $credentials['email']; // Keep the original case of the email

        // Find the user with the provided email (case-sensitive comparison later)
        $user = User::where('email', $email)->first();

        if (!$user) {
            // If user is not found, return an error
            return response()->json([
                'message' => 'Wrong Email',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the provided email matches exactly the stored email (case-sensitive)
        if ($user->email !== $email) {
            return response()->json([
                'message' => 'Wrong Email',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the password is correct
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Wrong Password',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Attempt login using JWT token generation
        $token = JWTAuth::attempt($credentials);

        if (!$token) {
            return $this->returnError('You Are unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();
        $serialNumber = $request->serial_number;

        // Handle serial number checking logic
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
}
