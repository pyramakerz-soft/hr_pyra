<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\UserVacation\StoreUserVacationRequest;
use Modules\Users\Enums\StatusEnum;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;

/**
 * @OA\Tag(
 *     name="Vacation",
 *     description="Operations related to user Vacation"
 * )
 */
class UserVacationController extends Controller
{
    use ResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/vacation/add_user_vacation",
     *     tags={"Vacation"},
     *     summary="Add a new vacation for the authenticated user",
     *     operationId="addUserVacation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={ "from", "to_date", "from_date"},
     *             @OA\Property(property="to_date", type="string", format="date", example="2025-02-27"),
     *             @OA\Property(property="from_date", type="string", format="date", example="2025-02-28"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Vacation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Vacation", type="object")
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
    public function addUserVacation(StoreUserVacationRequest $request)
    {
        $authUser = Auth::user();

        $from = Carbon::parse($request->input('from_date'));
        $to = Carbon::parse($request->input('to_date'));

        if ($to->lessThan($from)) {
            return $this->returnError('The end date cannot be before the start date', 422);
        }

        $vacationTypeId = $request->input('vacation_type_id');
        if ($vacationTypeId) {
            $vacationType = VacationType::findOrFail($vacationTypeId);
        } else {
            $defaultName = $from->greaterThan(Carbon::now()->addDays(2)->endOfDay()) ? 'اعتيادي' : 'عارضه';
            $vacationType = VacationType::where('name', $defaultName)->first();
            if (! $vacationType) {
                return $this->returnError("Vacation type '{$defaultName}' is not configured", 422);
            }
        }

        $daysCount = $from->diffInDays($to) + 1;

        $balance = $this->getOrCreateBalance($authUser, $vacationType, $from);

        if ($balance->remaining_days < $daysCount) {
            return $this->returnError('Insufficient ' . $vacationType->name . ' balance. Remaining days: ' . $balance->remaining_days, 422);
        }

        $vacation = UserVacation::create([
            'user_id' => $authUser->id,
            'vacation_type_id' => $vacationType->id,
            'from_date' => $from,
            'to_date' => $to,
            'days_count' => $daysCount,
            'status' => StatusEnum::Pending,
            'approval_of_direct' => StatusEnum::Pending,
            'approval_of_head' => StatusEnum::Pending,
        ]);

        return $this->returnData('Vacation', $vacation->fresh(['vacationType']), 'User Vacations Data');
    }

    /**
     * @OA\Get(
     *     path="/api/vacation/show_user_vacations",
     *     tags={"Vacation"},
     *     summary="Show all vacations for the authenticated user",
     *     operationId="showUserVacations",
     *     @OA\Response(
     *         response=200,
     *         description="List of user vacations",
     *         @OA\JsonContent(
     *             @OA\Property(property="Vacations", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function showUserVacations(Request $request)
    {
        $authUser = Auth::user();

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }

        $vacations = $authUser->user_vacations()
            ->with('vacationType')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $request->query('page', 1));

        return $this->returnData('Vacation', $vacations, 'User Vacations Data');
    }

    /**
     * @OA\Post(
     *     path="/api/vacation/change_vacation_status/{vacation}",
     *     tags={"Vacation"},
     *     summary="Change the status of a specific vacation",
     *     operationId="changeVacationStatus",
     *     @OA\Parameter(
     *         name="vacation",
     *         in="path",
     *         required=true,
     *         description="Vacation ID",
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
     *         description="Vacation status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Vacation", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vacation not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to change status"
     *     )
     * )
     */
    public function changeVacationStatus(Request $request, $vacationId)
    {
        $request->validate([
            'status' => 'required|in:approved,declined',
            'approver' => 'nullable|in:direct,head',
        ]);

        $vacation = UserVacation::with(['user.subDepartment', 'user.department', 'vacationType'])->find($vacationId);

        if (! $vacation) {
            return $this->returnError('Vacation not found', 404);
        }

        $manager = Auth::user();
        $employeeIds = $manager->getManagedEmployeeIds();

        if (! $employeeIds->contains($vacation->user_id)) {
            return $this->returnError('You are not authorized to update this vacation', 403);
        }

        $role = strtolower($manager->getRoleName() ?? '');
        $teamLeadId = optional($vacation->user->subDepartment)->teamlead_id;
        $departmentManagerId = optional($vacation->user->department)->manager_id;

        $approver = $request->input('approver');
        if (! $approver) {
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
        $previousOverall = $this->statusValue($vacation->status);

        if ($approver === 'direct') {
            $allowed = $manager->id === $teamLeadId || in_array($role, ['team leader', 'hr', 'admin']);
            if (! $allowed) {
                return $this->returnError('You do not have permission to approve as direct manager', 403);
            }

            $vacation->approval_of_direct = StatusEnum::from($status);
            $vacation->direct_approved_by = $manager->id;
        } elseif ($approver === 'head') {
            $allowed = $manager->id === $departmentManagerId || in_array($role, ['manager', 'hr', 'admin']);
            if (! $allowed) {
                return $this->returnError('You do not have permission to approve as head manager', 403);
            }

            $vacation->approval_of_head = StatusEnum::from($status);
            $vacation->head_approved_by = $manager->id;
        } else {
            return $this->returnError('Invalid approver type provided', 422);
        }

        $newOverall = $this->resolveOverallStatus($vacation);

        if ($previousOverall !== StatusEnum::Approved->value && $newOverall === StatusEnum::Approved->value) {
            $balance = $this->getOrCreateBalance($vacation->user, $vacation->vacationType, Carbon::parse($vacation->from_date));
            if ($balance->remaining_days < ($vacation->days_count ?? 0)) {
                return $this->returnError('Insufficient ' . $vacation->vacationType->name . ' balance. Remaining days: ' . $balance->remaining_days, 422);
            }
        }

        $vacation->status = $newOverall;
        $vacation->save();

        $this->syncVacationBalance($vacation->fresh(['user', 'vacationType']), $previousOverall);

        return $this->returnData('Vacation', $vacation->fresh(['user', 'vacationType']), 'Vacation status updated successfully');
    }

   /**    }


   /**
 * @OA\Get(
 *     path="/api/vacation/get_vacations_of_manager_employees",
 *     tags={"Vacation"},
 *     summary="Get vacations of employees in the manager's department",
 *     operationId="getVacationsOfManagerEmployees",
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
 *         @OA\Schema(type="string", example="Ali")
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
 *                 enum={"pending", "approved", "declined", "rejected", "all"},
 *                 example="approved"
 *             ),
 *             collectionFormat="multi"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of vacations for employees in the manager's department",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="Vacations", type="object",
 *                 @OA\Property(property="data", type="array",
 *                     @OA\Items(type="object",
 *                         @OA\Property(property="vacation", type="object",
 *                             @OA\Property(property="id", type="integer", example=1),
 *                             @OA\Property(property="user_id", type="integer", example=103),
 *                             @OA\Property(property="from_date", type="string", format="date", example="2024-08-10"),
 *                             @OA\Property(property="to_date", type="string", format="date", example="2024-08-20"),
 *                             @OA\Property(property="reason", type="string", example="Annual leave")
 *                         ),
 *                         @OA\Property(property="user", type="object",
 *                             @OA\Property(property="id", type="integer", example=103),
 *                             @OA\Property(property="name", type="string", example="Ali Hassan"),
 *                             @OA\Property(property="email", type="string", format="email", example="ali.hassan@example.com")
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(property="pagination", type="object",
 *                     @OA\Property(property="total", type="integer", example=25),
 *                     @OA\Property(property="per_page", type="integer", example=6),
 *                     @OA\Property(property="current_page", type="integer", example=1),
 *                     @OA\Property(property="last_page", type="integer", example=5),
 *                     @OA\Property(property="next_page_url", type="string", example="http://yourapi.com/api/vacation/get_vacations_of_manager_employees?page=2"),
 *                     @OA\Property(property="prev_page_url", type="string", example=null)
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

    public function getVacationsOfManagerEmployees()
    {
        $manager = Auth::user();
        $employeeIds = $manager->getManagedEmployeeIds();

        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }

        $query = UserVacation::with(['user', 'vacationType'])
            ->whereIn('user_id', $employeeIds);

        $searchTerm = request()->query('searchTerm');
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $statusFilter = request()->query('status');
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $statuses = is_array($statusFilter) ? $statusFilter : [$statusFilter];
            $mappedStatuses = array_map(function ($status) {
                $status = strtolower($status);
                return $status === 'declined' ? StatusEnum::Rejected->value : $status;
            }, $statuses);

            $query->whereIn('status', $mappedStatuses);
        }

        if ($typeId = request()->query('vacation_type_id')) {
            $query->where('vacation_type_id', $typeId);
        }

        if ($from = request()->query('from_date')) {
            $query->whereDate('from_date', '>=', $from);
        }

        if ($to = request()->query('to_date')) {
            $query->whereDate('to_date', '<=', $to);
        }

        $perPage = (int) request()->query('per_page', 6);
        if ($perPage < 1) {
            $perPage = 6;
        }

        $vacations = $query->orderByDesc('from_date')
            ->paginate($perPage, ['*'], 'page', request()->query('page', 1));

        $vacationsWithUserData = collect($vacations->items())->map(function (UserVacation $vacation) {
            return [
                'vacation' => $vacation,
                'user' => $vacation->user,
                'vacation_type' => $vacation->vacationType,
            ];
        });

        return $this->returnData('Vacations', [
            'data' => $vacationsWithUserData,
            'pagination' => [
                'total' => $vacations->total(),
                'per_page' => $vacations->perPage(),
                'current_page' => $vacations->currentPage(),
                'last_page' => $vacations->lastPage(),
                'next_page_url' => $vacations->nextPageUrl(),
                'prev_page_url' => $vacations->previousPageUrl(),
            ]
        ], 'Vacations for employees in the departments managed by the authenticated user');
    }

    

    protected function getOrCreateBalance(User $user, VacationType $vacationType, Carbon $date): UserVacationBalance
    {
        return UserVacationBalance::firstOrCreate(
            [
                'user_id' => $user->id,
                'vacation_type_id' => $vacationType->id,
                'year' => $date->year,
            ],
            [
                'allocated_days' => $vacationType->default_days ?? 0,
                'used_days' => 0,
            ]
        );
    }

    protected function resolveOverallStatus(UserVacation $vacation): string
    {
        $direct = $this->statusValue($vacation->approval_of_direct);
        $head = $this->statusValue($vacation->approval_of_head);
        $requiresHeadApproval = (bool) optional(optional($vacation->user)->department)->manager_id;

        if ($direct === 'declined' || $head === 'declined') {
            return StatusEnum::Rejected->value;
        }

        if ($direct === 'approved') {
            if (! $requiresHeadApproval || $head === 'approved' || $head === null || $head === 'pending') {
                return StatusEnum::Approved->value;
            }
        }

        return StatusEnum::Pending->value;
    }

    protected function statusValue($status): ?string
    {
        if ($status instanceof StatusEnum) {
            return $status->value;
        }

        return $status ?: null;
    }

    protected function syncVacationBalance(UserVacation $vacation, ?string $previousOverall): void
    {
        $current = $this->statusValue($vacation->status);

        if ($previousOverall === $current) {
            return;
        }

        $balance = UserVacationBalance::where('user_id', $vacation->user_id)
            ->where('vacation_type_id', $vacation->vacation_type_id)
            ->where('year', Carbon::parse($vacation->from_date)->year)
            ->first();

        if (!$balance) {
            $vacation->loadMissing('vacationType', 'user');
            $type = $vacation->vacationType;
            $user = $vacation->user;

            if (!$type || !$user) {
                return;
            }

            $balance = $this->getOrCreateBalance($user, $type, Carbon::parse($vacation->from_date));
        }

        $days = (float) ($vacation->days_count ?? 0);

        if ($previousOverall === StatusEnum::Approved->value && $current !== StatusEnum::Approved->value) {
            $balance->used_days = max(0, ($balance->used_days ?? 0) - $days);
            $balance->save();

            return;
        }

        if ($previousOverall !== StatusEnum::Approved->value && $current === StatusEnum::Approved->value) {
            $balance->used_days = ($balance->used_days ?? 0) + $days;
            $balance->save();
        }
    }

}
