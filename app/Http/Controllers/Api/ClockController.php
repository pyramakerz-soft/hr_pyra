<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Http\Requests\Api\UpdateClockRequest;
use App\Models\ClockInOut;
use App\Models\User;
use App\Services\Api\Clock\ClockService;
use App\Traits\ClockTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class ClockController extends Controller
{
    use ResponseTrait, ClockTrait;
    protected $clockService;
    public function __construct(ClockService $clockService)
    {
        $this->clockService = $clockService;
    }
    public function allClocks(Request $request)
    {

        return $this->clockService->getAllClocks($request);

    }
    public function getUserClocksById(Request $request, User $user)
    {

        return $this->clockService->getUserClocksById($request, $user);

    }
    public function showUserClocks(Request $request)
    {
        return $this->clockService->showUserClocks($request);
    }

    public function getClockById(ClockInOut $clock)
    {
        return $this->clockService->getClockById($clock);
    }
    public function clockIn(ClockInRequest $request)
    {
        return $this->clockService->clockIn($request);
    }

    public function clockOut(ClockOutRequest $request)
    {

        return $this->clockService->clockOut($request);

    }
    public function updateUserClock(UpdateClockRequest $request, User $user, ClockInOut $clock)
    {

        return $this->clockService->updateUserClock($request, $user, $clock);

    }

}
