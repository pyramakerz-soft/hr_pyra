<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use App\Traits\ResponseTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as FacadesLog;
use Log;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Traits\AuthTrait;
use Modules\Users\Models\User;
use Modules\Users\Resources\ProfileResource;

class AuthController extends Controller
{
    use ResponseTrait, AuthTrait;
    public function __construct() {}

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
        // Log::info(['Status' => 'Fail','type' => 'Login',$credentials]);
        if (!$user) {
            FacadesLog::info(['Status' => 'Fail', 'type' => 'Login', 'creds' => $credentials]);
            return response()->json(['message' => 'Wrong Email or Password'], Response::HTTP_UNAUTHORIZED);
        }
        FacadesLog::info(['Status' => 'Success', 'mob' => $request->mob, 'type' => 'Login', 'creds' => $credentials, 'all_reqs' => $request->all()]);
        if ($request->mob) {
            if (is_null($user->mob)) {
                $user->update(['mob' => $request->mob]);
            } elseif ($user->mob !== $request->mob) {
                throw new \Exception('Your current mobile is different from the original logged-in phone (' . $user->mob . ')(' . $request->mob . ')', 406);
            }
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
     *   security={{"bearerAuth": {}}}, 
     *   @OA\Parameter(
     *     name="Authorization",
     *     in="header",
     *     required=true,
     *     description="Bearer Token (Example: 'Bearer {your_token}')",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="User profile data",
     *     @OA\JsonContent(
     *       @OA\Property(property="result", type="string", example="true"),
     *       @OA\Property(property="message", type="string", example="User Data"),
     *       @OA\Property(
     *         property="User",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=367),
     *         @OA\Property(property="name", type="string", example="Zyad Mohamed"),
     *         @OA\Property(property="national_id", type="string", example="30206040200853"),
     *         @OA\Property(property="image", type="string", nullable=true, example=null),
     *         @OA\Property(property="job_title", type="string", example="Flutter developer"),
     *         @OA\Property(property="department_id", type="integer", example=1),
     *         @OA\Property(property="department_name", type="string", example="Software"),
     *         @OA\Property(property="role_name", type="string", example="Employee"),
     *         @OA\Property(property="is_clocked_out", type="boolean", example=true),
     *         @OA\Property(property="clockIn", type="string", nullable=true, example=null),
     *         @OA\Property(property="total_hours", type="string", example="00:00:00"),
     *         @OA\Property(property="user_start_time", type="string", format="time", example="07:00:00"),
     *         @OA\Property(property="user_end_time", type="string", format="time", example="15:00:00"),
     *         @OA\Property(property="is_notify_by_location", type="boolean", example=false),
     *         @OA\Property(
     *           property="assigned_locations_user",
     *           type="array",
     *           @OA\Items(
     *             type="object",
     *             @OA\Property(property="location_id", type="integer", example=1),
     *             @OA\Property(property="location_name", type="string", example="Pyramakerz.Alex"),
     *             @OA\Property(property="location_start_time", type="string", format="time", example="07:00:00"),
     *             @OA\Property(property="location_end_time", type="string", format="time", example="15:00:00")
     *           )
     *         ),
     *         @OA\Property(property="work_home", type="boolean", example=true),
     *         @OA\Property(
     *           property="work_types",
     *           type="array",
     *           @OA\Items(type="string", example="site")
     *         )
     *       )
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
        // $user = User::find(1); // Replace with your actual user ID
        // dd($user->roles);

        return $this->returnData("User", new ProfileResource($user), "User Data");
    }

    /**
     * @OA\Post(
     *   path="/api/auth/remove_serial_number/{user_id}",
     *   summary="Remove Serial Number from User",
     *   tags={"Auth"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="user_id",
     *     in="path",
     *     required=true,
     *     description="User ID whose serial number needs to be removed",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Serial Number removed successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="user", type="string", example=""),
     *       @OA\Property(property="message", type="string", example="Serial Number of User removed Successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User has no serial number",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No Serial Number found for this user")
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

    public function removeSerialNumber($id)
    {
        // Find the user manually to handle cases where the user does not exist
        $user = User::find($id);

        if (!$user) {
            return $this->returnError('User not found', 404);
        }

        if ($user->serial_number) {
            $user->update([
                'serial_number' => null,
            ]);
            return $this->returnData('user', $user, 'Serial Number of User removed Successfully');
        }

        return $this->returnError('No Serial Number found for this user');
    }


    /**
     * @OA\Get(
     *   path="/api/auth/check_serial_number/{user_id}",
     *   summary="Check if User has a Serial Number",
     *   tags={"Auth"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="user_id",
     *     in="path",
     *     required=true,
     *     description="User ID to check serial number",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Serial Number Status",
     *     @OA\JsonContent(
     *       @OA\Property(property="has_serial_number", type="boolean", example=true)
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

    public function checkSerialNumber(User $user)
    {
        $has_serial_number = false;
        if ($user->serial_number) {
            $has_serial_number = true;
        }
        return $this->returnData('has_serial_number', $has_serial_number, "");
    }
}
