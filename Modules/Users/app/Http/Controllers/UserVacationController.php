<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\UserVacation\StoreUserVacationRequest;
use Modules\Users\Enums\StatusEnum;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;
use Modules\Users\Models\UserVacationBalance;
use Modules\Users\Models\VacationType;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Modules\Users\Models\Department;
use Modules\Users\Models\SubDepartment;
use Modules\Users\Models\LeaveAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;



/**
 * @OA\Tag(
 *     name="Vacation",
 *     description="Operations related to user Vacation"
 * )
 */
class UserVacationController extends Controller
{
    use ResponseTrait;

    const ANNUAL_LEAVE_NAME = 'Annual Leave';
    const CASUAL_LEAVE_NAME = 'Casual Leave';
    const EMERGENCY_LEAVE_NAME = 'Emergency Leave';
    const UNPAID_LEAVE_NAME = 'Unpaid Leave';
    const MATERNITY_LEAVE_NAME = 'Maternity Leave';
    const MARRIAGE_LEAVE_NAME = 'Marriage Leave';
    const HAJJ_LEAVE_NAME = 'Hajj / Umrah Leave';
    const EXCEPTIONAL_LEAVE_NAME = 'Exceptional Leave';
    const SICK_LEAVE_NAME = 'Sick Leave';
    /**
     * @OA\Post(
     *     path="/api/vacation/add_user_vacation",
     *     tags={"Vacation"},
     *     summary="Add a new vacation for the authenticated user",
     *     operationId="addUserVacation",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={ "vacation_type_id", "to_date", "from_date"},
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
        $isHalfDay = $request->input('is_half_day', false);

        if ($isHalfDay) {
            // Force single day for half-day requests
            $to = $from->copy();
            $daysCount = 0.5;
        } else {
            if ($to->lessThan($from)) {
                return $this->returnError('The end date cannot be before the start date', 422);
            }
            // Calculate business days excluding weekends
            $daysCount = $this->calculateBusinessDays($from, $to, $authUser);

            // If all days are weekends, return error
            if ($daysCount <= 0) {
                return $this->returnError('The selected date range contains only weekends. Please select working days.', 422);
            }
        }

        $vacationTypeId = $request->input('vacation_type_id');
        if ($vacationTypeId) {
            $vacationType = VacationType::findOrFail($vacationTypeId);
        } else {
            $defaultName = $from->isSameDay(Carbon::now()) ? self::EMERGENCY_LEAVE_NAME : self::CASUAL_LEAVE_NAME;
            $vacationType = VacationType::where('name', $defaultName)->first();
            if (!$vacationType) {
                return $this->returnError("Vacation type '{$defaultName}' is not configured", 422);
            }
        }

        // Validate Special Leave Eligibility
        $eligibilityError = $this->validateSpecialLeaveEligibility($authUser, $vacationType, $daysCount);
        if ($eligibilityError) {
            return $this->returnError($eligibilityError, 422);
        }

        // Validate B2B Department Casual/Emergency Limit
        $academicLimitError = $this->validateB2BCasualEmergencyLimit($authUser, $vacationType, $from, $to, $daysCount);
        if ($academicLimitError) {
            return $this->returnError($academicLimitError, 422);
        }

        // Check for overlapping vacations (pending or approved)
        $overlappingVacation = UserVacation::where('user_id', $authUser->id)
            ->whereIn('status', [StatusEnum::Pending->value, StatusEnum::Approved->value])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('from_date', [$from, $to])
                    ->orWhereBetween('to_date', [$from, $to])
                    ->orWhere(function ($q) use ($from, $to) {
                        $q->where('from_date', '<=', $from)
                            ->where('to_date', '>=', $to);
                    });
            })
            ->first();

        if ($overlappingVacation) {
            return $this->returnError('You already have a vacation request (pending or approved) that overlaps with the selected dates.', 422);
        }

        // Calculate available days for this vacation type
        $availableDays = $this->calculateAvailableDays($authUser, $vacationType, $from, $daysCount);

        $attachments = $request->file('attachments');

        // Create vacation(s) based on available balance
        return $this->createVacationRecords($authUser, $vacationType, $from, $to, $daysCount, $availableDays, $attachments);
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
            ->with(['vacationType', 'attachments'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $request->query('page', 1));

        $year = now()->year;
        $balances = [];

        // Annual Leave Balance
        $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
        if ($annualType) {
            $annualBalance = $authUser->vacationBalances()
                ->where('vacation_type_id', $annualType->id)
                ->where('year', $year)
                ->first();

            $allocated = (float) ($annualBalance->allocated_days ?? 0);
            $used = (float) ($annualBalance->used_days ?? 0);
            $remaining = $allocated - $used; // Allow negative values

            $balances[self::ANNUAL_LEAVE_NAME] = [
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => $remaining,
            ];
        }

        // Casual and Emergency Used Days (No balance info)
        $otherTypes = VacationType::whereIn('name', [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])->get();
        foreach ($otherTypes as $type) {
            $used = $this->calculateUsedDays($authUser, $type, $year);
            $balances[$type->name] = [
                'used' => $used,
            ];
        }

        $responseData = [
            'vacations' => $vacations,
            'balances' => $balances,
        ];

        return $this->returnData('data', $responseData, 'User Vacations Data');
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
            'status' => 'required|in:approved,declined,rejected,refused',
            'approver' => 'nullable|in:direct,head',
            'allow_future_balance' => 'nullable|boolean',
        ]);

        $vacation = UserVacation::with(['user.subDepartment', 'user.department', 'vacationType'])->find($vacationId);

        if (!$vacation) {
            return $this->returnError('Vacation not found', 404);
        }

        $manager = Auth::user();
        $employeeIds = $manager->getManagedEmployeeIds();

        if (!$employeeIds->contains($vacation->user_id)) {
            return $this->returnError('You are not authorized to update this vacation', 403);
        }

        $role = strtolower($manager->getRoleName() ?? '');
        $teamLeadId = optional($vacation->user->subDepartment)->teamlead_id;
        $departmentManagerId = optional($vacation->user->department)->manager_id;

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
        $previousOverall = $this->statusValue($vacation->status);

        if ($approver === 'direct') {
            $allowed = $manager->id === $teamLeadId || in_array($role, ['team leader', 'hr', 'admin']);
            if (!$allowed) {
                return $this->returnError('You do not have permission to approve as direct manager', 403);
            }

            $vacation->status = StatusEnum::from($status);
            $vacation->approval_of_direct = StatusEnum::from($status);
            $vacation->direct_approved_by = $manager->id;
        } elseif ($approver === 'head') {
            $allowed = $manager->id === $departmentManagerId || in_array($role, ['manager', 'hr', 'admin']);
            if (!$allowed) {
                return $this->returnError('You do not have permission to approve as head manager', 403);
            }

            $vacation->status = StatusEnum::from($status);
            $vacation->approval_of_head = StatusEnum::from($status);
            $vacation->head_approved_by = $manager->id;
        } else {
            return $this->returnError('Invalid approver type provided', 422);
        }


        // Check if manager allows future balance (negative balance)
        $allowFutureBalance = $request->input('allow_future_balance', false);

        if ($previousOverall !== StatusEnum::Approved->value) {
            // Skip balance checks if future balance is allowed
            if (!$allowFutureBalance) {
                // Check specific balance dynamically for Casual/Emergency
                if (in_array($vacation->vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
                    $usedDays = $this->calculateUsedDays($vacation->user, $vacation->vacationType, Carbon::parse($vacation->from_date)->year);
                    $allocatedDays = $vacation->vacationType->default_days ?? 0;
                    $remainingDays = max(0, $allocatedDays - $usedDays);

                    if ($remainingDays < ($vacation->days_count ?? 0)) {
                        return $this->returnError('Insufficient ' . $vacation->vacationType->name . ' balance. Remaining days: ' . $remainingDays, 422);
                    }

                    // Check Annual Leave balance
                    $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
                    if ($annualType) {
                        $annualBalance = $this->getOrCreateBalance($vacation->user, $annualType, Carbon::parse($vacation->from_date));
                        if ($annualBalance->remaining_days < ($vacation->days_count ?? 0)) {
                            return $this->returnError('Insufficient ' . self::ANNUAL_LEAVE_NAME . ' balance. Remaining days: ' . $annualBalance->remaining_days, 422);
                        }
                    }
                } elseif ($vacation->vacationType->name !== self::UNPAID_LEAVE_NAME && $vacation->vacationType->name !== self::SICK_LEAVE_NAME) {
                    // Standard check for other types (excluding Unpaid)
                    $balance = $this->getOrCreateBalance($vacation->user, $vacation->vacationType, Carbon::parse($vacation->from_date));
                    if ($balance->remaining_days < ($vacation->days_count ?? 0)) {
                        return $this->returnError('Insufficient ' . $vacation->vacationType->name . ' balance. Remaining days: ' . $balance->remaining_days, 422);
                    }
                }
            }
        }
        // Change vacation type to Exceptional Leave if future balance is allowed
        if ($allowFutureBalance && $status === 'approved' && $vacation->vacationType->name == self::UNPAID_LEAVE_NAME) {
            $exceptionalLeaveType = VacationType::where('name', self::EXCEPTIONAL_LEAVE_NAME)->first();
            if ($exceptionalLeaveType) {
                $vacation->setRelation('vacationType', $exceptionalLeaveType);
                $vacation->vacation_type_id = $exceptionalLeaveType->id;
            }
        }

        $vacation->save();

        $this->syncVacationBalance($vacation, $previousOverall, $allowFutureBalance);

        // Fetch updated balances to return
        $user = $vacation->user;
        $year = Carbon::parse($vacation->from_date)->year;
        $balances = [];

        // Annual Leave Balance
        $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
        if ($annualType) {
            $annualBalance = $user->vacationBalances()
                ->where('vacation_type_id', $annualType->id)
                ->where('year', $year)
                ->first();

            $allocated = (float) ($annualBalance->allocated_days ?? 0);
            $used = (float) ($annualBalance->used_days ?? 0);
            $remaining = $allocated - $used;

            $balances[self::ANNUAL_LEAVE_NAME] = [
                'allocated' => $allocated,
                'used' => $used,
                'remaining' => $remaining,
            ];
        }

        // Casual and Emergency Used Days
        $otherTypes = VacationType::whereIn('name', [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])->get();
        foreach ($otherTypes as $type) {
            $used = $this->calculateUsedDays($user, $type, $year);
            $balances[$type->name] = [
                'used' => $used,
            ];
        }

        $responseData = $vacation->fresh(['user', 'vacationType'])->toArray();
        $responseData['balances'] = $balances;

        return $this->returnData('Vacation', $responseData, 'Vacation status updated successfully');
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

        $query = UserVacation::with(['user', 'vacationType', 'attachments'])
            ->whereIn('user_id', $employeeIds)->orderBy('created_at', 'desc');

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
            $user = $vacation->user;
            $year = Carbon::parse($vacation->from_date)->year;
            $balances = [];

            // Annual Leave Balance
            $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
            if ($annualType) {
                $annualBalance = $user->vacationBalances()
                    ->where('vacation_type_id', $annualType->id)
                    ->where('year', $year)
                    ->first();

                $allocated = (float) ($annualBalance->allocated_days ?? 0);
                $used = (float) ($annualBalance->used_days ?? 0);
                $remaining = $allocated - $used; // Allow negative values

                $balances[self::ANNUAL_LEAVE_NAME] = [
                    'allocated' => $allocated,
                    'used' => $used,
                    'remaining' => $remaining,
                ];
            }

            // Casual and Emergency Used Days
            $otherTypes = VacationType::whereIn('name', [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])->get();
            foreach ($otherTypes as $type) {
                $used = $this->calculateUsedDays($user, $type, $year);
                $balances[$type->name] = [
                    'used' => $used,
                ];
            }

            $vacation['balances'] = $balances;

            return $vacation;
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


    /**
     * @OA\Post(
     *     path="/api/users/import-vacation-balances",
     *     tags={"User"},
     *     summary="Import and update user vacation balances from an Excel file",
     *     security={{"bearerAuth": {}}},
     *     description="This endpoint allows you to update vacation balances for multiple users from an Excel file (.xlsx).",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="file",
     *                     type="file",
     *                     description="Excel file (.xlsx) with columns: code, vacation_type, year, balance"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vacation balances updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vacation balances updated successfully. 5 records processed.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid file format or validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid file format. Please upload an Excel file (.xlsx).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Data validation error in the Excel file",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Row 2: User with code 'EMP-001' not found.")
     *         )
     *     )
     * )
     */
    public function importVacationBalances(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx',
        ]);

        if ($validator->fails()) {
            return $this->returnError('Invalid file format. Please upload an Excel file (.xlsx).', 400);
        }

        $file = $request->file('file');

        $vacationType = VacationType::where('name', 'Annual Leave')->first();
        $year = Carbon::now()->year;

        try {
            $rows = Excel::toArray([], $file)[0];
            $header = array_map('trim', array_map('strtolower', array_shift($rows)));

            $requiredHeaders = ['code', 'balance'];
            $missingHeaders = array_diff($requiredHeaders, $header);
            if (!empty($missingHeaders)) {
                return $this->returnError('Missing required columns: ' . implode(', ', $missingHeaders), 422);
            }

            $allErrors = [];
            $updatedCount = 0;

            DB::beginTransaction();

            foreach ($rows as $rowIndex => $row) {
                $rowData = array_combine($header, $row);
                $rowErrors = [];

                $user = User::where('code', $rowData['code'])->first();
                if (!$user) {
                    $rowErrors[] = "User with code '{$rowData['code']}' not found.";
                }


                if (!is_numeric($rowData['balance'])) {
                    $rowErrors[] = "Invalid balance '{$rowData['balance']}'. Must be a number.";
                }

                if (!empty($rowErrors)) {
                    $allErrors[] = "Row " . ($rowIndex + 2) . ": " . implode(' ', $rowErrors);
                    continue;
                }

                UserVacationBalance::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'vacation_type_id' => $vacationType->id,
                        'year' => $year
                    ],
                    [
                        'allocated_days' => $rowData['balance']
                    ]
                );
                $updatedCount++;
            }

            if (!empty($allErrors)) {
                DB::rollBack();
                return $this->returnError(implode("\n", $allErrors), 422);
            }

            DB::commit();

            return $this->returnSuccess("Vacation balances updated successfully. {$updatedCount} records processed.", 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('An error occurred while processing the file: ' . $e->getMessage(), 500);
        }
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


    protected function statusValue($status): ?string
    {
        if ($status instanceof StatusEnum) {
            return $status->value;
        }

        return $status ?: null;
    }

    protected function syncVacationBalance(UserVacation $vacation, ?string $previousOverall, bool $allowFutureBalance = false): void
    {
        $current = $this->statusValue($vacation->status);

        // For Unpaid Leave with future balance, deduct from Annual Leave instead
        if (
            $allowFutureBalance &&
            $vacation->vacationType->name === self::EXCEPTIONAL_LEAVE_NAME &&
            $previousOverall !== StatusEnum::Approved->value &&
            $current === StatusEnum::Approved->value
        ) {

            $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
            if ($annualType) {
                $annualBalance = $this->getOrCreateBalance($vacation->user, $annualType, Carbon::parse($vacation->from_date));
                $annualBalance->used_days = ($annualBalance->used_days ?? 0) + ($vacation->days_count ?? 0);
                $annualBalance->save();
            }
            return;
        }

        // Skip balance updates for Unpaid Leave (unless using future balance above)
        if ($vacation->vacationType->name === self::UNPAID_LEAVE_NAME) {
            return;
        }

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
            // Update specific balance
            $balance->used_days = max(0, ($balance->used_days ?? 0) - $days);
            $balance->save();

            // Revert Annual Leave balance if Casual or Emergency
            $this->updateAnnualBalance($vacation, -$days);

            return;
        }

        if ($previousOverall !== StatusEnum::Approved->value && $current === StatusEnum::Approved->value) {
            // Update specific balance
            $balance->used_days = ($balance->used_days ?? 0) + $days;
            $balance->save();

            // Deduct from Annual Leave balance if Casual or Emergency
            $this->updateAnnualBalance($vacation, $days);
        }
    }

    protected function updateAnnualBalance(UserVacation $vacation, float $days): void
    {
        if (in_array($vacation->vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
            $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
            if ($annualType) {
                $annualBalance = $this->getOrCreateBalance($vacation->user, $annualType, Carbon::parse($vacation->from_date));
                $annualBalance->used_days = max(0, ($annualBalance->used_days ?? 0) + $days);
                $annualBalance->save();
            }
        }
    }

    protected function calculateUsedDays(User $user, VacationType $type, int $year): float
    {
        $balance = $user->vacationBalances()
            ->where('vacation_type_id', $type->id)
            ->where('year', $year)
            ->first();

        return $balance ? (float) $balance->used_days : 0.0;
    }


    /**
     * @OA\Get(
     *     path="/api/vacation/types",
     *     tags={"Vacation"},
     *     summary="Get all vacation types",
     *     operationId="getVacationTypes",
     *     @OA\Response(
     *         response=200,
     *         description="List of vacation types",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function getVacationTypes()
    {
        if (auth()->user()->hasRole('Hr')) {
            $types = VacationType::all();
        } else {
            $types = VacationType::all()->where('name', '!=', 'Annual Leave')->where('name', '!=', 'Official Holiday')->where('name', '!=', 'Exceptional Leave');
        }
        return $this->returnData('data', $types, 'Vacation Types');
    }

    protected function validateSpecialLeaveEligibility(User $user, VacationType $type, float $daysCount): ?string
    {
        if ($type->name === self::MATERNITY_LEAVE_NAME) {
            // Gender is stored in users table as 'm' or 'f'
            if (strtolower($user->gender ?? '') === 'm') {
                return 'Maternity leave is only applicable for female employees.';
            }
        }

        if ($type->name === self::HAJJ_LEAVE_NAME) {
            // Check if user has taken Hajj leave before
            $hasTakenHajj = UserVacation::where('user_id', $user->id)
                ->where('vacation_type_id', $type->id)
                ->where('status', StatusEnum::Approved)
                ->exists();

            if ($hasTakenHajj) {
                return 'Hajj / Umrah leave can only be taken once.';
            }

            // Check tenure: User must be employed for >= 5 years
            $userDetail = $user->user_detail;
            if ($userDetail && $userDetail->hiring_date) {
                $hiringDate = Carbon::parse($userDetail->hiring_date);
                $yearsEmployed = $hiringDate->diffInYears(Carbon::now());

                if ($yearsEmployed < 5) {
                    return 'Hajj / Umrah leave is only available after 5 years of employment.';
                }
            } else {
                return 'Unable to verify employment tenure. Hiring date not found.';
            }

            // Check days limit
            if ($daysCount > 30) {
                return 'Hajj / Umrah leave is limited to a maximum of 30 days. You requested ' . $daysCount . ' days.';
            }
        }

        if ($type->name === self::MARRIAGE_LEAVE_NAME) {
            // Check days limit
            if ($daysCount > 10) {
                return 'Marriage leave is limited to a maximum of 10 days. You requested ' . $daysCount . ' days.';
            }
        }

        return null;
    }

    protected function calculateBusinessDays(Carbon $from, Carbon $to, User $user): float
    {
        $businessDays = 0;
        $current = $from->copy();

        // Check if user works on Saturday
        $worksOnSaturday = false;
        $userDetail = $user->user_detail;
        if ($userDetail && $userDetail->works_on_saturday !== null) {
            $worksOnSaturday = (bool) $userDetail->works_on_saturday;
        }

        while ($current->lessThanOrEqualTo($to)) {
            $dayOfWeek = $current->dayOfWeek;

            // Friday is 5, Saturday is 6 in Carbon (0 = Sunday, 6 = Saturday)
            $isFriday = $dayOfWeek === Carbon::FRIDAY;
            $isSaturday = $dayOfWeek === Carbon::SATURDAY;

            // Exclude Friday (always weekend)
            // Exclude Saturday only if user doesn't work on Saturday
            if (!$isFriday && !($isSaturday && !$worksOnSaturday)) {
                $businessDays++;
            }

            $current->addDay();
        }

        return $businessDays;
    }

    protected function calculateAvailableDays(User $user, VacationType $vacationType, Carbon $date, float $daysCount): float
    {
        // For Casual and Emergency leave, check both specific limit and Annual Leave balance
        if (in_array($vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
            $usedDays = $this->calculateUsedDays($user, $vacationType, $date->year);
            $allocatedDays = $vacationType->default_days ?? 0;
            $remainingSpecific = max(0, $allocatedDays - $usedDays);

            $remainingAnnual = 0;
            $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
            if ($annualType) {
                $annualBalance = $this->getOrCreateBalance($user, $annualType, $date);
                $remainingAnnual = $annualBalance->remaining_days;
            }

            return min($remainingSpecific, $remainingAnnual);
        }

        // For Unpaid Leave, unlimited availability
        if ($vacationType->name === self::UNPAID_LEAVE_NAME) {
            return $daysCount;
        }

        if ($vacationType->name === self::SICK_LEAVE_NAME) {
            return $daysCount;
        }


        // For other leave types (Annual, Maternity, Marriage, Hajj), check balance
        $balance = $this->getOrCreateBalance($user, $vacationType, $date);
        return $balance->remaining_days;
    }

    protected function createVacationRecords(User $user, VacationType $vacationType, Carbon $from, Carbon $to, float $daysCount, float $availableDays, $attachments = null)
    {
        // Sufficient balance - create single vacation record
        if ($availableDays >= $daysCount) {
            $vacation = UserVacation::create([
                'user_id' => $user->id,
                'vacation_type_id' => $vacationType->id,
                'from_date' => $from,
                'to_date' => $to,
                'days_count' => $daysCount,
                'status' => StatusEnum::Pending,
                'approval_of_direct' => StatusEnum::Pending,
                'approval_of_head' => StatusEnum::Pending,
            ]);

            $this->processAttachments($vacation, $attachments);

            return $this->returnData('Vacation', $vacation->fresh(['vacationType']), 'User Vacation created successfully');
        }

        // No balance - convert to full unpaid
        if ($availableDays <= 0) {
            $unpaidType = VacationType::where('name', self::UNPAID_LEAVE_NAME)->first();
            if (!$unpaidType) {
                return $this->returnError('Insufficient balance and Unpaid Leave type is not configured.', 422);
            }

            $vacation = UserVacation::create([
                'user_id' => $user->id,
                'vacation_type_id' => $unpaidType->id,
                'from_date' => $from,
                'to_date' => $to,
                'days_count' => $daysCount,
                'status' => StatusEnum::Pending,
                'approval_of_direct' => StatusEnum::Pending,
                'approval_of_head' => StatusEnum::Pending,
            ]);

            $this->processAttachments($vacation, $attachments);

            return $this->returnData('Vacation', $vacation->fresh(['vacationType']), 'Insufficient balance. Request converted to Unpaid Leave.');
        }

        // Partial balance - split into paid and unpaid
        $unpaidType = VacationType::where('name', self::UNPAID_LEAVE_NAME)->first();
        if (!$unpaidType) {
            return $this->returnError('Insufficient balance for full request and Unpaid Leave type is not configured.', 422);
        }

        return DB::transaction(function () use ($user, $vacationType, $unpaidType, $from, $to, $availableDays, $daysCount, $attachments) {
            // 1. Paid Portion - find the end date that represents availableDays working days
            $paidTo = $from->copy();

            // Keep extending paidTo until we have the required number of business days
            while ($this->calculateBusinessDays($from, $paidTo, $user) < $availableDays) {
                $paidTo->addDay();
            }

            $vacationPaid = UserVacation::create([
                'user_id' => $user->id,
                'vacation_type_id' => $vacationType->id,
                'from_date' => $from,
                'to_date' => $paidTo,
                'days_count' => $availableDays,
                'status' => StatusEnum::Pending,
                'approval_of_direct' => StatusEnum::Pending,
                'approval_of_head' => StatusEnum::Pending,
            ]);

            $this->processAttachments($vacationPaid, $attachments);

            // 2. Unpaid Portion
            $unpaidFrom = (clone $paidTo);
            if (floor($availableDays) == $availableDays) {
                $unpaidFrom->addDay();
            }

            $unpaidDays = $daysCount - $availableDays;
            $vacationUnpaid = UserVacation::create([
                'user_id' => $user->id,
                'vacation_type_id' => $unpaidType->id,
                'from_date' => $unpaidFrom,
                'to_date' => $to,
                'days_count' => $unpaidDays,
                'status' => StatusEnum::Pending,
                'approval_of_direct' => StatusEnum::Pending,
                'approval_of_head' => StatusEnum::Pending,
            ]);

            return $this->returnData('Vacation', [
                'paid_vacation' => $vacationPaid->fresh(['vacationType']),
                'unpaid_vacation' => $vacationUnpaid->fresh(['vacationType'])
            ], 'Insufficient balance. Request split into Paid (' . $availableDays . ' days) and Unpaid (' . $unpaidDays . ' days) Leave.');
        });
    }

    protected function processAttachments(UserVacation $vacation, $attachments)
    {
        if (!$attachments) {
            return;
        }

        if (!is_array($attachments)) {
            $attachments = [$attachments];
        }

        $destinationPath = public_path('storage/attachments');

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        foreach ($attachments as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $safeBaseName = Str::slug($baseName ?: 'attachment');
            $uniqueName = $safeBaseName . '-' . uniqid();
            $fileName = $extension ? "{$uniqueName}.{$extension}" : $uniqueName;

            $file->move($destinationPath, $fileName);
            $fileUrl = asset('storage/attachments/' . $fileName);

            LeaveAttachment::create([
                'user_vacation_id' => $vacation->id,
                'file_path' => $fileUrl,
                'file_name' => $fileName,
                'mime_type' => $file->getClientMimeType(),
            ]);
        }
    }

    /**
     * Validate B2B Department Casual/Emergency Leave Limit
     *
     */
    protected function validateB2BCasualEmergencyLimit($user, $vacationType, $from, $to, $daysCount): ?string
    {
        // Only apply to Emergency and Casual Leave for B2B department
        $targetTypes = [self::EMERGENCY_LEAVE_NAME, self::CASUAL_LEAVE_NAME];
        if (!in_array($vacationType->name, $targetTypes)) {
            return null;
        }

        $department = $user->department;
        if (!$department || strtolower($department->name) !== 'b2b') {
            return null;
        }

        // Only apply to users with role 'employee'
        if (strtolower($user->getRoleName()) !== 'employee') {
            return null;
        }

        // Check if request falls during academic year (Sept 1 - June 30)
        if (!$this->isAcademicYearPeriod($from, $to)) {
            return null;
        }

        // Check if request falls within Half Year Vacation (Dec 26 - Feb 10)
        if ($this->isHalfYearVacation($from, $to)) {
            return null;
        }

        $academicYearStart = $this->getAcademicYearStart($from);
        $academicYearEnd = $this->getAcademicYearEnd($from);
        $daysTaken = UserVacationBalance::select('used_days')
            ->where('user_id', $user->id)
            ->where('vacation_type_id', $vacationType->id)
            ->where('year', $from->year)
            ->first();
        $daysTaken = UserVacation::where('user_id', $user->id)
            ->whereHas('vacationType', function ($q) use ($targetTypes) {
                $q->whereIn('name', $targetTypes);
            })
            ->where(function ($q) {
                $q->where('status', StatusEnum::Approved)
                    ->orWhere('status', StatusEnum::Pending);
            })
            ->where(function ($q) use ($academicYearStart, $academicYearEnd) {
                $q->whereBetween('from_date', [$academicYearStart, $academicYearEnd])
                    ->orWhereBetween('to_date', [$academicYearStart, $academicYearEnd]);
            })
            ->get()
            ->filter(function ($vacation) {
                // Filter out vacations that fall within the Half Year Vacation period
                return !$this->isHalfYearVacation(Carbon::parse($vacation->from_date), Carbon::parse($vacation->to_date));
            })
            ->sum('days_count');

        if (($daysTaken + $daysCount) > 3) {
            return "B2B staff can only take a maximum of 3 days combined for Emergency and Casual leave during the academic year (Sept-June), excluding Half Year Vacation (Dec 26 - Feb 10). You have already requested {$daysTaken} days.";
        }

        return null;
    }

    /**
     * Check if the period falls within the Half Year Vacation (Dec 26 - Feb 10)
     */
    protected function isHalfYearVacation($from, $to): bool
    {
        $year = $from->year;
        // Half Year Vacation starts Dec 26 of current academic year start year
        // Ends Feb 10 of next year

        // Determine the relevant academic year start for the given date
        $academicYearStart = $this->getAcademicYearStart($from);
        $startYear = $academicYearStart->year;

        $halfYearStart = Carbon::create($startYear, 12, 26);
        $halfYearEnd = Carbon::create($startYear + 1, 2, 10);

        // Check if the requested period overlaps with the Half Year Vacation
        // Logic: (StartA <= EndB) and (EndA >= StartB)
        return $from->lessThanOrEqualTo($halfYearEnd) && $to->greaterThanOrEqualTo($halfYearStart);
    }

    /**
     * Check if the period falls within the academic year (Sept-June)
     *
     */
    protected function isAcademicYearPeriod($from, $to): bool
    {
        // Academic year: Sept 1 - June 30
        // Simplification: Check if start date is in academic year.
        $month = $from->month;
        return ($month >= 9 || $month <= 6);
    }

    /**
     * Get the start date of the academic year for a given date
     *
     */
    protected function getAcademicYearStart($date): Carbon
    {
        $year = $date->year;
        $month = $date->month;

        // If current month is Sept-Dec, academic year started this year (Sept 1)
        // If current month is Jan-June, academic year started last year (Sept 1)
        if ($month >= 9) {
            return Carbon::create($year, 9, 1);
        } else {
            return Carbon::create($year - 1, 9, 1);
        }
    }

    /**
     * Get the end date of the academic year for a given date
     *
     */
    protected function getAcademicYearEnd($date): Carbon
    {
        $start = $this->getAcademicYearStart($date);
        return $start->copy()->addYear()->month(6)->endOfMonth(); // June 30 of next year relative to start
    }

    /**
     * @OA\Post(
     *     path="/api/users/reset-vacation-balance",
     *     tags={"User"},
     *     summary="Reset vacation balances for a user, department, or sub-department",
     *     security={{"bearerAuth": {}}},
     *     description="Resets vacation balances for the specified target (user, department, or sub-department) for a given year.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", description="ID of the user to reset"),
     *             @OA\Property(property="department_id", type="integer", description="ID of the department to reset (includes sub-departments)"),
     *             @OA\Property(property="sub_department_id", type="integer", description="ID of the sub-department to reset"),
     *             @OA\Property(property="year", type="integer", description="Year to reset balances for (default: current year)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vacation balances reset successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Vacation balances reset successfully for 10 users.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please provide user_id, department_id, or sub_department_id.")
     *         )
     *     )
     * )
     */
    public function resetUsersVacationBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'year' => 'nullable|integer|min:2000|max:2100',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 400);
        }

        if (!$request->user_id && !$request->department_id && !$request->sub_department_id) {
            return $this->returnError('Please provide user_id, department_id, or sub_department_id.', 400);
        }

        $year = $request->input('year', Carbon::now()->year);
        $userIds = collect();

        if ($request->user_id) {
            $userIds->push($request->user_id);
        } elseif ($request->department_id) {
            $department = Department::find($request->department_id);
            if ($department) {
                $userIds = $department->employees()->pluck('id');
            }
        } elseif ($request->sub_department_id) {
            $subDepartment = SubDepartment::find($request->sub_department_id);
            if ($subDepartment) {
                $userIds = $subDepartment->users()->pluck('id');
            }
        }

        if ($userIds->isEmpty()) {
            return $this->returnError('No users found for the specified criteria.', 404);
        }

        DB::transaction(function () use ($userIds, $year) {
            $vacationTypes = VacationType::all();

            foreach ($userIds as $userId) {
                foreach ($vacationTypes as $type) {
                    $allocatedDays = $type->default_days ?? 0;

                    // Specific logic for Annual Leave: Reset to 0
                    if ($type->name === self::ANNUAL_LEAVE_NAME) {
                        $allocatedDays = 0;
                    }

                    UserVacationBalance::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'vacation_type_id' => $type->id,
                            'year' => $year,
                        ],
                        [
                            'allocated_days' => $allocatedDays,
                            'used_days' => 0,
                        ]
                    );
                }
            }
        });

        return $this->returnSuccess("Vacation balances reset successfully for " . $userIds->count() . " users.", 200);
    }
}
