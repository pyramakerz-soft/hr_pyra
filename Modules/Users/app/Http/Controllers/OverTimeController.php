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
use Modules\Users\Models\OverTime;
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

        // Calculate overtime minutes if both from and to are provided
        $overtimeMinutes = null;
        if ($request->input('from') && $request->input('to')) {
            $from = \Carbon\Carbon::parse($request->input('from'));
            $to = \Carbon\Carbon::parse($request->input('to'));
            $overtimeMinutes = $from->diffInMinutes($to); // Calculate from 'from' to 'to'
        }

        // Create new overtime
        $userOvertime = OverTime::create([
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'status' => StatusEnum::Pending,
            'user_id' => $authUser->id,
            'reason' => $request->input('reason'),
            'mission_flag' => $request->input('mission_flag'),
            'overtime_minutes' => $overtimeMinutes,
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
        $userOvertime = OverTime::find($request->input('overtime_id'));

        if (!$userOvertime) {
            return $this->returnError('Overtime record not found', 404);
        }

        // Validate end time is not before start time
        if ($userOvertime->from && $request->input('to')) {
            $from = \Carbon\Carbon::parse($userOvertime->from);
            $to = \Carbon\Carbon::parse($request->input('to'));

            if ($to->lessThan($from)) {
                return $this->returnError('End time cannot be before start time', 422);
            }

            $overtimeMinutes = $from->diffInMinutes($to); // Calculate from 'from' to 'to'
        } else {
            $overtimeMinutes = null;
        }

        // Update the overtime record
        $userOvertime->update([
            'to' => $request->input('to'),
            'user_id' => $authUser->id,
            'overtime_minutes' => $overtimeMinutes,
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
        return $this->returnData('OverTimeData', [
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
            'status' => 'required|in:approved,declined',
            'approver' => 'nullable|in:direct,head',
        ]);

        $overtime = Overtime::with(['user.subDepartment', 'user.department'])->find($overtimeId);

        if (!$overtime) {
            return $this->returnError('Overtime not found', 404);
        }

        $manager = Auth::user();
        $employeeIds = $manager->getManagedEmployeeIds();

        if (!$employeeIds->contains($overtime->user_id)) {
            return $this->returnError('You are not authorized to update this overtime', 403);
        }

        $role = strtolower($manager->getRoleName() ?? '');
        $teamLeadId = optional($overtime->user->subDepartment)->teamlead_id;
        $departmentManagerId = optional($overtime->user->department)->manager_id;

        $approver = $request->input('approver');
        if (!$approver) {
            if ($manager->id === $teamLeadId) {
                $approver = 'direct';
            } elseif ($manager->id === $departmentManagerId) {
                $approver = 'head';
            } elseif ($role === 'team leader') {
                $approver = 'direct';
            } else {
                $approver = 'head';
            }
        }

        $status = $request->input('status');

        if ($approver === 'direct') {
            $allowed = $manager->id === $teamLeadId || in_array($role, ['team leader', 'hr', 'admin']);
            if (!$allowed) {
                return $this->returnError('You do not have permission to approve as direct manager', 403);
            }

            $overtime->approval_of_direct = $status;
            $overtime->direct_approved_by = $manager->id;
        } elseif ($approver === 'head') {
            $allowed = $manager->id === $departmentManagerId || in_array($role, ['manager', 'hr', 'admin']);
            if (!$allowed) {
                return $this->returnError('You do not have permission to approve as head manager', 403);
            }

            $overtime->approval_of_head = $status;
            $overtime->head_approved_by = $manager->id;
        } else {
            return $this->returnError('Invalid approver type provided', 422);
        }

        $overtime->save();

        return $this->returnData('Overtime', $overtime->fresh(['user']), 'Overtime status updated successfully');
    }

    /**
  * @OA\Get(
  *     path="/api/overtime/get_overtime_of_manager_employees",
  *     tags={"Overtime"},
  *     summary="Get overtimes of employees in the manager's department",
  *     operationId="getOvertimeOfManagerEmployees",
  *     security={{ "bearerAuth": {} }},
  *     @OA\Parameter(
  *         name="page",
  *         in="query",
  *         description="Page number for pagination",
  *         required=false,
  *         @OA\Schema(type="integer", example=1)
  *     ),
  *     @OA\Parameter(
  *         name="searchTerm",
  *         in="query",
  *         description="Filter by employee name (partial match allowed)",
  *         required=false,
  *         @OA\Schema(type="string", example="Sarah")
  *     ),
  *     @OA\Parameter(
  *         name="status",
  *         in="query",
  *         description="Filter by status: pending, approved, rejected or 'all'. You can also pass multiple values (e.g. status[]=pending&status[]=approved)",
  *         required=false,
  *         @OA\Schema(
  *             type="array",
  *             @OA\Items(
  *                 type="string",
  *                 enum={"pending", "approved", "rejected", "all"},
  *                 example="approved"
  *             ),
  *             collectionFormat="multi"
  *         )
  *     ),
  *     @OA\Response(
  *         response=200,
  *         description="List of overtimes for employees in the manager's department",
  *         @OA\JsonContent(
  *             @OA\Property(property="status", type="string", example="success"),
  *             @OA\Property(property="OverTimes", type="object",
  *                 @OA\Property(property="data", type="array",
  *                     @OA\Items(type="object",
  *                         @OA\Property(property="overTime", type="object",
  *                             @OA\Property(property="id", type="integer", example=1),
  *                             @OA\Property(property="user_id", type="integer", example=102),
  *                             @OA\Property(property="from", type="string", format="datetime", example="2024-08-01 17:00:00"),
  *                             @OA\Property(property="to", type="string", format="datetime", example="2024-08-01 20:00:00"),
  *                             @OA\Property(property="reason", type="string", example="Project delivery")
  *                             @OA\Property(property="location_id", type="integer", example=1),
  *                             @OA\Property(property="status", type="string", example="pending")
  *                         ),
  *                         @OA\Property(property="user", type="object",
  *                             @OA\Property(property="id", type="integer", example=102),
  *                             @OA\Property(property="name", type="string", example="Sarah Connor"),
  *                             @OA\Property(property="email", type="string", format="email", example="sarah.connor@example.com")
  *                         )
  *                     )
  *                 ),
  *                 @OA\Property(property="pagination", type="object",
  *                     @OA\Property(property="total", type="integer", example=50),
  *                     @OA\Property(property="per_page", type="integer", example=6),
  *                     @OA\Property(property="current_page", type="integer", example=1),
  *                     @OA\Property(property="last_page", type="integer", example=9),
  *                     @OA\Property(property="next_page_url", type="string", nullable=true, example="http://yourapi.com/api/overtime/get_overtime_of_manager_employees?page=2"),
  *                     @OA\Property(property="prev_page_url", type="string", nullable=true, example=null)
  *                 )
  *             )
  *         )
  *     ),
  *     @OA\Response(
  *         response=403,
  *         description="Unauthorized"
  *     ),
  *     @OA\Response(
  *         response=404,
  *         description="Manager not assigned to any department or no employees found"
  *     )
  * )

      */
    public function getOvertimeOfManagerEmployees()
    {
        $manager = Auth::user();

        $employeeIds = $manager->getManagedEmployeeIds();

        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }

        // Date range: 26th of previous month to 26th of current month
        //  $currentDate = Carbon::now();
        //  if ($currentDate->day > 26) {
        //      $startDate = $currentDate->copy()->setDay(26);
        //      $endDate = $currentDate->copy()->addMonth()->setDay(26);
        //  } else {
        //      $startDate = $currentDate->copy()->subMonth()->setDay(26);
        //      $endDate = $currentDate->copy()->setDay(26);
        //  }

        $searchTerm = request()->query('searchTerm');
        $statusFilter = request()->query('status');

        // Build query
        $query = Overtime::whereIn('user_id', $employeeIds)

            ->with('user');

        // Filter by employee name
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Filter by status if not 'all' or null
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if (is_array($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // Sort by latest created
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $overTimes = $query->paginate(6, ['*'], 'page', request()->query('page', 1));

        // Format response
        $overTimeWithUserData = collect($overTimes->items())->map(function ($overTime) {
            return [
                'overTime' => $overTime,
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
