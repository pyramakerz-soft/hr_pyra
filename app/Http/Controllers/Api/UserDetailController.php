<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserDetailResource;
use App\Models\UserDetail;
use App\Traits\ResponseTrait;

class UserDetailController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
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
     * Display the specified resource.
     */
    public function show(UserDetail $userDetail)
    {
        return $this->returnData("UserDetail", new UserDetailResource($userDetail), "User Data");
    }

}
