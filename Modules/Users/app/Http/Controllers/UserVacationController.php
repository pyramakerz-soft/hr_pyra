<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\UserVacation\StoreUserVacationRequest;
use Modules\Users\Models\Vacation;
use Modules\Users\Models\User;
use Modules\Users\Models\UserVacation;

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
        $userVacation = UserVacation::create([
            'to_date' => $request->input('to_date'),
            'from_date' => $request->input('from_date'),
            'status' => $request->input('status', 'pending'), // Default to 'pending' if not provided
            'user_id' => $authUser->id,
        ]);

        return $this->returnData('Vacation', $userVacation, 'User Vacations Data');
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

        // Fetch and paginate the user's vacations
        $userVacations = $authUser->user_vacations()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->returnData('Vacation', $userVacations, 'User Vacations Data');
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
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $vacation = UserVacation::find($vacationId);

        if (!$vacation) {
            return $this->returnError('Vacation not found', 404);
        }

        $authUser = Auth::user();
        $employeeIds =   $authUser->getManagedEmployeeIds();

        if (!in_array($vacation->user_id, $employeeIds->toArray())) {
            return $this->returnError('You are not authorized to update this vacation', 403);
        }

        // If the user is the manager or the vacation belongs to the authenticated user
        $vacation->status = $request->input('status');
        $vacation->save();

        return $this->returnData('Vacation', $vacation, 'Vacation status updated successfully');
    }


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
 *                 enum={"pending", "approved", "rejected", "all"},
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
    
        // Define the date range (26th of previous month to 26th of current month)
        $currentDate = Carbon::now();
        if ($currentDate->day > 26) {
            $startDate = $currentDate->copy()->setDay(26);
            $endDate = $currentDate->copy()->addMonth()->setDay(26);
        } else {
            $startDate = $currentDate->copy()->subMonth()->setDay(26);
            $endDate = $currentDate->copy()->setDay(26);
        }
    
        $searchTerm = request()->query('searchTerm');
        $statusFilter = request()->query('status');
    
        // Build the query
        $query = UserVacation::whereIn('user_id', $employeeIds)
            ->whereBetween('from_date', [$startDate, $endDate])
            ->with('user');
    
        // Filter by employee name
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }
    
        // Filter by vacation status
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            if (is_array($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            } else {
                $query->where('status', $statusFilter);
            }
        }
    
        // Sort by created_at descending
        $query->orderBy('created_at', 'desc');
    
        // Paginate
        $vacations = $query->paginate(6, ['*'], 'page', request()->query('page', 1));
    
        // Format data
        $vacationsWithUserData = collect($vacations->items())->map(function ($vacation) {
            return [
                'vacation' => $vacation,
                'user' => $vacation->user,
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
    
}
