<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Users\Http\Requests\Api\Excuses\StoreExcusesRequest;
use Modules\Users\Models\Excuse;
use Modules\Users\Models\User;

/**
 * @OA\Tag(
 *     name="Excuses",
 *     description="Operations related to user excuses"
 * )
 */
class ExcuseController extends Controller
{
    use ResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/excuse/add_user_excuse",
     *     tags={"Excuses"},
     *     summary="Add a new excuse for the authenticated user",
     *     operationId="addUserExcuse",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"date", "from", "to", "status"},
     *             @OA\Property(property="date", type="string", format="date", example="2025-02-27"),
     *             @OA\Property(property="from", type="string", example="09:00"),
     *             @OA\Property(property="to", type="string", example="17:00"),
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Excuse created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuse", type="object")
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
    public function addUserExcuse(StoreExcusesRequest $request)
    {
        //TODO make a limit two excuses per month DONE
        $authUser = Auth::user();

        $requestedDate = Carbon::parse($request->input('date'));
        if ($requestedDate->day > 25) {
            $startOfMonth = $requestedDate->copy()->day(26)->startOfDay();
            $endOfMonth = $requestedDate->copy()->addMonth()->day(25)->endOfDay();
        } else {
            $startOfMonth = $requestedDate->copy()->subMonth()->day(26)->startOfDay();
            $endOfMonth = $requestedDate->copy()->day(25)->endOfDay();
        }

        $excusesCount = Excuse::where('user_id', $authUser->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        if ($excusesCount >= 2) {
            return $this->returnError('You have reached the limit of 2 excuses for ' . $requestedDate->format('F Y') . '.', 422);
        }

        $userExcuse = Excuse::create([
            'date' => $request->input('date'),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'status' => $request->input('status', 'pending'),
            'user_id' => $authUser->id,
        ]);

        return $this->returnData('Excuse', $userExcuse, 'User Excuses Data');
    }

    /**
     * @OA\Get(
     *     path="/api/excuse/show_user_excuses",
     *     tags={"Excuses"},
     *     summary="Show all excuses for the authenticated user",
     *     operationId="showUserExcuses",
     *     @OA\Response(
     *         response=200,
     *         description="List of user excuses",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuses", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function showUserExcuses(Request $request)
    {
        $authUser = Auth::user();

        // Fetch and paginate the user's excuses
        $userExcuses = $authUser->excuses()
            ->orderBy('created_at', 'desc')
            ->paginate(6);

        return $this->returnData('Excuses', $userExcuses, 'User Excuses Data');
    }

    /**
     * @OA\Post(
     *     path="/api/excuse/change_excuse_status/{excuse}",
     *     tags={"Excuses"},
     *     summary="Change the status of a specific excuse",
     *     operationId="changeExcuseStatus",
     *     @OA\Parameter(
     *         name="excuse",
     *         in="path",
     *         required=true,
     *         description="Excuse ID",
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
     *         description="Excuse status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="Excuse", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Excuse not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized to change status"
     *     )
     * )
     */
    public function changeExcuseStatus(Request $request, $excuseId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $excuse = Excuse::find($excuseId);

        if (!$excuse) {
            return $this->returnError('Excuse not found', 404);
        }

        $authUser = Auth::user();


        $employeeIds = $authUser->getManagedEmployeeIds();

        if (!in_array($excuse->user_id, $employeeIds->toArray())) {
            return $this->returnError('You are not authorized to update this excuse', 403);
        }

        $excuse->status = $request->input('status');
        $excuse->save();

        return $this->returnData('Excuse', $excuse, 'Excuse status updated successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/excuse/get_excuses_of_manager_employees",
     *     tags={"Excuses"},
     *     summary="Get excuses of employees in the manager's department",
     *     operationId="getExcusesOfManagerEmployees",
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
     *         @OA\Schema(type="string", example="John")
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
     *                 example="pending"
     *             ),
     *             collectionFormat="multi"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of excuses for employees in the manager's department",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="Excuses", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="excuse", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="user_id", type="integer", example=101),
     *                             @OA\Property(property="date", type="string", format="date", example="2024-08-01"),
     *                             @OA\Property(property="reason", type="string", example="Medical leave")
     *                         ),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=101),
     *                             @OA\Property(property="name", type="string", example="John Doe"),
     *                             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="total", type="integer", example=50),
     *                     @OA\Property(property="per_page", type="integer", example=6),
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="last_page", type="integer", example=9),
     *                     @OA\Property(property="next_page_url", type="string", nullable=true, example="http://yourapi.com/api/excuse/get_excuses_of_manager_employees?page=2"),
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
    public function getExcusesOfManagerEmployees()
    {
        $manager = Auth::user();

        $employeeIds = $manager->getManagedEmployeeIds();

        if ($employeeIds->isEmpty()) {
            return $this->returnError('No employees found under this manager', 404);
        }

        // Date range: 26th of previous month to 26th of current month
        // $currentDate = Carbon::now();
        // if ($currentDate->day > 26) {
        //     $startDate = $currentDate->copy()->setDay(26);
        //     $endDate = $currentDate->copy()->addMonth()->setDay(26);
        // } else {
        //     $startDate = $currentDate->copy()->subMonth()->setDay(26);
        //     $endDate = $currentDate->copy()->setDay(26);
        // }

        $searchTerm = request()->query('searchTerm');
        $statusFilter = request()->query('status');

        // Build the base query
        $query = Excuse::whereIn('user_id', $employeeIds)
            ->with('user');

        // Apply search term filter if provided
        if (!empty($searchTerm)) {
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Apply status filter if provided and not "all"
        if (!empty($statusFilter) && $statusFilter !== 'all') {
            // You can also support multiple status values if needed:
            if (is_array($statusFilter)) {
                $query->whereIn('status', $statusFilter);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        // Order by latest created_at
        $query->orderBy('created_at', 'desc');

        // Paginate the results
        $excuses = $query->paginate(6, ['*'], 'page', request()->query('page', 1));

        // Format response
        $excusesWithUserData = collect($excuses->items())->map(function ($excuse) {
            return [
                'excuse' => $excuse,
            ];
        });

        return $this->returnData('Excuses', [
            'data' => $excusesWithUserData,
            'pagination' => [
                'total' => $excuses->total(),
                'per_page' => $excuses->perPage(),
                'current_page' => $excuses->currentPage(),
                'last_page' => $excuses->lastPage(),
                'next_page_url' => $excuses->nextPageUrl(),
                'prev_page_url' => $excuses->previousPageUrl(),
            ]
        ], 'Excuses for employees in the departments managed by the authenticated user');
    }

}
