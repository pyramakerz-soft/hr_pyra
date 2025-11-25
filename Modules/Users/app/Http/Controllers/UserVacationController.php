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

        // Calculate available days for this vacation type
        $availableDays = $this->calculateAvailableDays($authUser, $vacationType, $from, $daysCount);

        // Create vacation(s) based on available balance
        return $this->createVacationRecords($authUser, $vacationType, $from, $to, $daysCount, $availableDays);
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
        //TODO add vication balance to this response, first check if it needed for every vacation type
        $authUser = Auth::user();

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 1) {
            $perPage = 10;
        }

        $vacations = $authUser->user_vacations()
            ->with('vacationType')
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
            'status' => 'required|in:approved,declined',
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

            $vacation->approval_of_direct = StatusEnum::from($status);
            $vacation->direct_approved_by = $manager->id;
        } elseif ($approver === 'head') {
            $allowed = $manager->id === $departmentManagerId || in_array($role, ['manager', 'hr', 'admin']);
            if (!$allowed) {
                return $this->returnError('You do not have permission to approve as head manager', 403);
            }

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
                } elseif ($vacation->vacationType->name !== self::UNPAID_LEAVE_NAME) {
                    // Standard check for other types (excluding Unpaid)
                    $balance = $this->getOrCreateBalance($vacation->user, $vacation->vacationType, Carbon::parse($vacation->from_date));
                    if ($balance->remaining_days < ($vacation->days_count ?? 0)) {
                        return $this->returnError('Insufficient ' . $vacation->vacationType->name . ' balance. Remaining days: ' . $balance->remaining_days, 422);
                    }
                }
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

            return [
                'vacation' => $vacation,
                'balances' => $balances,
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
                    ['user_id' => $user->id,],
                    ['allocated_days' => $rowData['balance']]
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
            $vacation->vacationType->name === self::UNPAID_LEAVE_NAME &&
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

        // For Casual/Emergency, we only update Annual Leave balance
        if (in_array($vacation->vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
            $annualType = VacationType::where('name', self::ANNUAL_LEAVE_NAME)->first();
            if (!$annualType) {
                return;
            }
            $vacation->loadMissing('vacationType', 'user'); // Ensure user and vacationType are loaded
            $type = $annualType;
            $user = $vacation->user;

            if (!$type || !$user) {
                return;
            }
            // Re-fetch or create balance for annual leave
            $balance = $this->getOrCreateBalance($user, $type, Carbon::parse($vacation->from_date));
        }


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
            // Only update balance if NOT Casual/Emergency
            if (!in_array($vacation->vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
                $balance->used_days = max(0, ($balance->used_days ?? 0) - $days);
                $balance->save();
            }

            // Revert Annual Leave balance if Casual or Emergency
            $this->updateAnnualBalance($vacation, -$days);

            return;
        }

        if ($previousOverall !== StatusEnum::Approved->value && $current === StatusEnum::Approved->value) {
            // Only update balance if NOT Casual/Emergency
            if (!in_array($vacation->vacationType->name, [self::CASUAL_LEAVE_NAME, self::EMERGENCY_LEAVE_NAME])) {
                $balance->used_days = ($balance->used_days ?? 0) + $days;
                $balance->save();
            }

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
        return (float) UserVacation::where('user_id', $user->id)
            ->where('vacation_type_id', $type->id)
            ->where('status', StatusEnum::Approved)
            ->whereYear('from_date', $year)
            ->sum('days_count');
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
        $types = VacationType::all()->where('name', '!=', 'Annual Leave')->where('name', '!=', 'Official Holiday');
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
                    return 'Hajj / Umrah leave is only available after 5 years of employment. You have been employed for ' . $yearsEmployed . ' year(s).';
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

        // For other leave types (Annual, Maternity, Marriage, Hajj), check balance
        $balance = $this->getOrCreateBalance($user, $vacationType, $date);
        return $balance->remaining_days;
    }

    protected function createVacationRecords(User $user, VacationType $vacationType, Carbon $from, Carbon $to, float $daysCount, float $availableDays)
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

            return $this->returnData('Vacation', $vacation->fresh(['vacationType']), 'Insufficient balance. Request converted to Unpaid Leave.');
        }

        // Partial balance - split into paid and unpaid
        $unpaidType = VacationType::where('name', self::UNPAID_LEAVE_NAME)->first();
        if (!$unpaidType) {
            return $this->returnError('Insufficient balance for full request and Unpaid Leave type is not configured.', 422);
        }

        return DB::transaction(function () use ($user, $vacationType, $unpaidType, $from, $to, $availableDays, $daysCount) {
            // 1. Paid Portion
            $paidTo = (clone $from)->addDays(ceil($availableDays) - 1);

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
}
