<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserDetailResource;
use App\Models\UserDetail;
use App\Traits\ResponseTrait;

/**
 * @OA\Schema(
 *   schema="UserDetail",
 *   type="object",
 *   required={"salary", "hourly_rate", "overtime_hourly_rate", "working_hours_day", "overtime_hours", "start_time", "end_time", "emp_type", "hiring_date", "user_id"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="salary", type="number", format="float", example=60000.00),
 *   @OA\Property(property="hourly_rate", type="number", format="float", example=25.00),
 *   @OA\Property(property="overtime_hourly_rate", type="number", format="float", example=37.50),
 *   @OA\Property(property="working_hours_day", type="number", format="float", example=8.00),
 *   @OA\Property(property="overtime_hours", type="number", format="float", example=2.00),
 *   @OA\Property(property="start_time", type="string", format="time", example="08:00:00"),
 *   @OA\Property(property="end_time", type="string", format="time", example="17:00:00"),
 *   @OA\Property(property="emp_type", type="string", example="full-time"),
 *   @OA\Property(property="hiring_date", type="string", format="date", example="2024-01-01"),
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="created_at", type="string", format="date-time", example="2024-10-01T12:00:00Z"),
 *   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-10-01T12:30:00Z")
 * )
 */
class UserDetailController extends Controller
{
    use ResponseTrait;
    public function __construct()
    {
        // $this->middleware("permission:user-detail-list")->only(['index', 'show']);
    }

    /**
     * @OA\Get(
     *   path="/api/user_details",
     *   summary="Get a list of user details",
     *   tags={"UserDetail"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="List of user details",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/UserDetail")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="No user details found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="No User Details Found")
     *     )
     *   )
     * )
     */
    public function index()
    {
        $user_details = UserDetail::all();
        if ($user_details->isEmpty()) {
            return $this->returnError('No User Details Found');
        }
        $data['user_details'] = UserDetailResource::collection($user_details);
        return $this->returnData('data', ($data), 'User Details Data');
    }

    /**
     * @OA\Get(
     *   path="/api/user_details/{userDetail}",
     *   summary="Get a specific user detail",
     *   tags={"UserDetail"},
     *   security={{"bearerAuth": {}}},
     *   @OA\Parameter(
     *     name="userDetail",
     *     in="path",
     *     description="ID of the user detail",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="User detail data",
     *     @OA\JsonContent(ref="#/components/schemas/UserDetail")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User detail not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="User Detail Not Found")
     *     )
     *   )
     * )
     */
    public function show(UserDetail $userDetail)
    {
        return $this->returnData("UserDetail", new UserDetailResource($userDetail), "User Data");
    }

}
