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
