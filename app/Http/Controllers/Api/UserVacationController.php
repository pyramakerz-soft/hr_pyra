<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserVacationRequest;
use App\Http\Requests\Api\UpdateUserVacationRequest;
use App\Models\UserVacation;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class UserVacationController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userVacations = UserVacation::all();
        if ($userVacations->isEmpty()) {
            return $this->returnError('No User Vacations Found');
        }
        return $this->returnData('userVacations', $userVacations, 'User Vacations Data');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserVacationRequest $request)
    {
        $userVacation = UserVacation::create($request->validated());
        return $this->returnData('userVacation', $userVacation, 'User Vacation Stored');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserVacation $userVacation)
    {
        return $this->returnData('UserVacation', $userVacation, 'User Vacation Data');

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserVacationRequest $request, UserVacation $userVacation)
    {
        $userVacation->update($request->validated());
        return $this->returnData('UserVacation', $userVacation, 'User Vacation Updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserVacation $userVacation)
    {
        $userVacation->delete();
        return $this->returnData('UserVacation', $userVacation, 'User Vacation deleted');
    }
    public function profile()
    {
        $authUser = Auth::user();
        $userVacations = UserVacation::where('user_id', $authUser->id)->get();
        if (!$userVacations) {
            return $this->returnError('No User Vacations Found for this User');

        }
        return $this->returnData("UserVacations", ($userVacations), "UserVacations Data");
    }
}
