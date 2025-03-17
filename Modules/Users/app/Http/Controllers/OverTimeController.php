<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Enums\StatusEnum;
use Modules\Users\Http\Requests\Api\OverTime\EndUserOverTimeRequest;
use Modules\Users\Http\Requests\Api\OverTime\StartUserOverTimeRequest;
use Modules\Users\Models\Overtime;  // Ensure you have an Overtime model
use Modules\Users\Models\User;

/**
 * @OA\Tag(
 *     name="Overtime",
 *     description="Operations related to user overtime"
 * )
 */
class OverTimeController extends Controller
{
    use ResponseTrait;

  /**
 * @OA\Post(
 *     path="/api/overtime/start_user_overtime",
 *     tags={"Overtime"},
 *     summary="start a new overtime for the authenticated user",
 *     operationId="startUserOvertime",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={ "from", },
 *             @OA\Property(property="to", type="string", format="date", example="2025-02-27"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User Overtime created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="Overtime", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function addStartUserOvertime(StartUserOverTimeRequest $request)
{
    $request->validated();
    $authUser = Auth::user();

    // Create new overtime
    $userOvertime = Overtime::create([
        'from' => $request->input('from'),
        'status' => StatusEnum::Pending,
        'user_id' => $authUser->id,
    ]);

    // Return the created overtime data in the response
    return response()->json([
        'Overtime' => $userOvertime,
        'message' => 'User Overtime created successfully'
    ], 200);
}



    
    /**
     * @OA\Post(
     *     path="/api/overtime/end_user_overtime",
     *     tags={"Overtime"},
     *     summary="end a overtime for the authenticated user",
     *     operationId="endUserOvertime",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={ "to","reason" },
     *             @OA\Property(property="reason", type="string", format="reason", example="bla bla"),

     *             @OA\Property(property="to", type="string", example="2025-02-27"),

     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Overtime created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Overtime", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function addEndUserOvertime(EndUserOverTimeRequest $request)
    {
        $request->validated();
    
        $authUser = Auth::user();
    
        // Find the overtime record by its ID
        $userOvertime = Overtime::find($request->overtime_id);
    
  
            // Update the overtime record
            $userOvertime->update([
                'to' => $request->input('to'),
                'reason' => $request->input('reason'),
                'user_id' => $authUser->id,
            ]);
    
            // Return the updated overtime data
            return $this->returnData('Overtime', $userOvertime, 'User Overtime Data');
    }
    

    /**
     * @OA\Get(
     *     path="/api/overtime/show_user_overtime",
     *     tags={"Overtime"},
     *     summary="Show all overtimes for the authenticated user",
     *     operationId="showUserOvertime",
     *     @OA\Response(
     *         response=200,
     *         description="List of user overtimes",
     *         @OA\JsonContent(
     *             @OA\Property(property="Overtimes", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function showUserOvertime(Request $request)
    {
        $authUser = Auth::user();
    
        // Fetch and paginate the user's overtime
        $userOvertime = $authUser->overTimes()
            ->orderBy('created_at', 'desc')
            ->paginate(6);
    
        // Fetch the ongoing overtime (if any) where the 'to' column is null
        $ongoingOvertime = $authUser->overTimes()
            ->whereNull('to')  // 'to' column is null, indicating it's an ongoing overtime
            ->first();  // Get the first ongoing overtime (if any)
    
        // Return the data along with the ongoing overtime if any
        return $this->returnData('OverTimeData',[
            'Overtimes' => $userOvertime,
            'OngoingOvertime' => $ongoingOvertime,
        ], 'User Overtimes');
    }
    

    /**
     * @OA\Post(
     *     path="/api/overtime/change_overtime_status/{overtime}",
     *     tags={"Overtime"},
     *     summary="Change the status of a specific overtime",
     *     operationId="changeOvertimeStatus",
     *     @OA\Parameter(
     *         name="overtime",
     *         in="path",
     *         required=true,
     *         description="Overtime ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Overtime status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Overtime", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Overtime not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to change status"
     *     )
     * )
     */
    public function changeOvertimeStatus(Request $request, $overtimeId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $overtime = Overtime::find($overtimeId);

        if (!$overtime) {
            return $this->returnError('Overtime not found', 404);
        }

        $authUser = Auth::user();
        $department = $overtime->user->department;

    $managerIds = $department->managers()->pluck('users.id')->toArray();

    if (!in_array($authUser->id, $managerIds) && $overtime->user_id != $authUser->id) {
        return $this->returnError('You are not authorized to update this overtime', 403);
    }

        $overtime->status = $request->input('status');
        $overtime->save();

        return $this->returnData('Overtime', $overtime, 'Overtime status updated successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/overtime/get_overtime_of_manager_employees",
     *     tags={"Overtime"},
     *     summary="Get overtimes of employees in the manager's department",
     *     operationId="getOvertimeOfManagerEmployees",
     *     @OA\Response(
     *         response=200,
     *         description="List of overtimes for employees in the manager's department",
     *         @OA\JsonContent(
     *             @OA\Property(property="Overtimes", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */public function getOvertimeOfManagerEmployees()
{
    $manager = Auth::user();

    // Get department IDs where the user is a manager
    $departmentIds = $manager->managedDepartments()->pluck('departments.id');

    if ($departmentIds->isEmpty()) {
        return $this->returnError('Manager is not assigned to any department', 404);
    }

    // Get employees under the manager
    $employeeIds = User::whereIn('department_id', $departmentIds)
        ->where('id', '!=', $manager->id)
        ->pluck('id');

    if ($employeeIds->isEmpty()) {
        return $this->returnError('No employees found under this manager', 404);
    }

    // Get all parent managers
    $parentManagers = $manager->allParentManagers();

    // Exclude employees who are managers at any level above
    $parentManagerIds = $parentManagers->pluck('id')->toArray();
    $employeeIds = $employeeIds->diff($parentManagerIds);

    // Define the date range (26th of previous month to 26th of current month)
    $currentDate = Carbon::now();
    if ($currentDate->day > 26) {
        $startDate = $currentDate->copy()->setDay(26);
        $endDate = $currentDate->copy()->addMonth()->setDay(26);
    } else {
        $startDate = $currentDate->copy()->subMonth()->setDay(26);
        $endDate = $currentDate->copy()->setDay(26);
    }

    // Fetch overtime records
    $overTimes = Overtime::whereIn('user_id', $employeeIds)
        ->whereBetween('from', [$startDate, $endDate])
        ->whereNotNull('to')
        ->with('user')
        ->paginate(6, ['*'], 'page', request()->query('page', 1));

    // Format response
    $overTimeWithUserData = collect($overTimes->items())->map(function ($overTime) {
        return [
            'overTime' => $overTime,
            'user' => $overTime->user,
        ];
    });

    return $this->returnData('OverTimes', [
        'data' => $overTimeWithUserData,
        'pagination' => [
            'total' => $overTimes->total(),
            'per_page' => $overTimes->perPage(),
            'current_page' => $overTimes->currentPage(),
            'last_page' => $overTimes->lastPage(),
            'next_page_url' => $overTimes->nextPageUrl(),
            'prev_page_url' => $overTimes->previousPageUrl(),
        ]
    ], 'OverTimes for employees in the departments managed by the authenticated user');
}

}
