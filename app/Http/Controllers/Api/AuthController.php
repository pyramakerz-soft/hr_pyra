<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Traits\AuthTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ResponseTrait, AuthTrait;
    public function __construct()
    {
        $this->middleware('auth:api')->except('login');
    }

    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   summary="Login to the application",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="email", type="string", format="email", example="hr@test.com"),
     *       @OA\Property(property="password", type="string", format="password", example="123456"),
     *       @OA\Property(property="serial_number", type="string", example="")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Successful login",
     *     @OA\JsonContent(
     *       @OA\Property(property="result", type="string", example="true"),
     *       @OA\Property(property="token", type="string", example="jwt_token")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Wrong Email")
     *     )
     *   ),
     *   @OA\Response(
     *     response=406,
     *     description="Serial number does not match",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Serial number does not match")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Server Error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="An error occurred")
     *     )
     *   )
     * )
     */

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // Validate user credentials
        $user = $this->validateUser($credentials);

        if (!$user) {
            return response()->json(['message' => 'Wrong Email or Password'], Response::HTTP_UNAUTHORIZED);
        }

        // Handle serial number checking logic
        $this->validateSerialNumber($request, $user);

        // Generate and refresh token if necessary
        $token = $this->generateToken($request, $user);

        //return response with token
        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *   path="/api/auth/logout",
     *   summary="Logout from the application",
     *   tags={"Auth"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="Successfully logged out",
     *     @OA\JsonContent(
     *       @OA\Property(property="result", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="You are logged out")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Unauthorized")
     *     )
     *   )
     * )
     */
    public function logout()
    {
        $user = auth()->user();
        Auth::logout();
        return $this->returnData("user", $user, "You are logged out");
    }
    /**
     * @OA\Get(
     *   path="/api/auth/user_by_token",
     *   summary="Get the authenticated user's profile",
     *   tags={"Auth"},
     *   @OA\Response(
     *     response=200,
     *     description="User profile data",
     *     @OA\JsonContent(
     *       @OA\Property(property="user", type="object"),
     *       @OA\Property(property="message", type="string", example="User Data")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No User Found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="You are not authenticated")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Server Error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="An error occurred")
     *     )
     *   )
     * )
     */
    public function profile()
    {
        $authUser = Auth::user();
        $user = User::where('id', $authUser->id)->first();
        if (!$user) {
            return $this->returnError('No User Found');
        }

        return $this->returnData("User", new ProfileResource($user), "User Data");
    }
    public function removeSerialNumber(User $user)
    {

        if ($user->serial_number) {
            $user->update([
                'serial_number' => null,
            ]);
            return $this->returnData('user', "", 'Serial Number of User removed Successfully');
        }
        return $this->returnError('No Serial Number found for this user');
    }
    public function checkSerialNumber(User $user)
    {
        $has_serial_number = false;
        if ($user->serial_number) {
            $has_serial_number = true;
        }
        return $this->returnData('has_serial_number', $has_serial_number, "");
    }
}
