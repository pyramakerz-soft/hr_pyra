<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\OverTime\StoreUserOverTimeRequest;
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
     *     path="/api/overtime/add_user_overtime",
     *     tags={"Overtime"},
     *     summary="Add a new overtime for the authenticated user",
     *     operationId="addUserOvertime",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date", "from", "to","reason" ,"status"},
     *             @OA\Property(property="date", type="string", format="date", example="2025-02-27"),
     *             @OA\Property(property="reason", type="string", format="reason", example="bla bla"),

     *             @OA\Property(property="from", type="string", example="09:00"),
     *             @OA\Property(property="to", type="string", example="17:00"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
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
    public function addUserOvertime(StoreUserOverTimeRequest $request)
    {
        $authUser = Auth::user();
        $userOvertime = Overtime::create([

            'date' => $request->input('date'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'reason' => $request->input('reason'),
            'status' => $request->input('status', 'pending'), // Default to 'pending' if not provided
            'user_id' => $authUser->id,
        ]);

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
            ->paginate(10);

        return $this->returnData('Overtime', $userOvertime, 'User Overtime Data');
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
     */
    public function getOvertimeOfManagerEmployees()
    {
        $manager = Auth::user();

        // Get department IDs where the user is a manager
        $departmentIds = $manager->managedDepartments()->pluck('departments.id');

        if ($departmentIds->isEmpty()) {
            return $this->returnError('Manager is not assigned to any department', 404);
        }

        // Get all employees in those departments
        $employeeIds = User::whereIn('department_id', $departmentIds)
            ->where('id', '!=', $manager->id) // Exclude the manager
            ->pluck('id');
// return     $employeeIds;
        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }

        // Define the date range (26th of previous month to 26th of current month)
        $currentDate = Carbon::now();
        if ($currentDate->day > 26) {
            $startDate = $currentDate->copy()->setDay(26);
            $endDate = $currentDate->copy()->addMonth()->setDay(26);
        } else {
            $startDate = $currentDate->copy()->subMonth()->setDay(26);
            $endDate = $currentDate->copy()->setDay(26);
        }

        // Fetch overtime records for employees in the managed departments
        $overtimes = Overtime::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return $this->returnData('Overtime', $overtimes, 'Overtime for employees in the departments managed by the authenticated user');
    }
}
